<x-app-layout>
    @php $title = 'Live Radar - ' . $trip->title; @endphp

    <div class="flex flex-col h-[calc(100vh-80px)] -mt-stack-lg -mx-container-padding-mobile md:-mx-container-padding-desktop">
        <!-- Overlay Header -->
        <div class="absolute top-20 left-4 right-4 md:left-8 md:right-8 z-[1000] pointer-events-none">
            <div class="flex justify-between items-start">
                <div class="bg-surface-container-lowest/80 backdrop-blur-md p-4 rounded-2xl shadow-elevation-2 pointer-events-auto max-w-sm border border-surface-variant/30">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-primary animate-pulse" style="font-variation-settings: 'FILL' 1;">my_location</span>
                        <h2 class="font-headline text-headline-sm text-on-surface">Live Radar</h2>
                    </div>
                    <p class="font-body text-[12px] text-on-surface-variant mb-4">Membagikan lokasi secara real-time ke sesama anggota grup. Lokasi hanya dibagikan selama halaman ini terbuka.</p>
                    
                    <div class="flex flex-col gap-2" id="online-members">
                        <div class="flex items-center gap-2 text-outline">
                            <span class="material-symbols-outlined animate-spin text-[16px]">sync</span>
                            <span class="font-label text-[11px]">Connecting to radar...</span>
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('trips.show', $trip) }}" class="btn-secondary bg-surface-container-lowest/80 backdrop-blur-md border-transparent shadow-elevation-2 pointer-events-auto">
                    <span class="material-symbols-outlined">close</span> Tutup
                </a>
            </div>
        </div>

        <!-- The Map -->
        <div id="radar-map" class="flex-1 w-full h-full z-0"></div>
    </div>

    <!-- JS Logic for Leaflet & Reverb -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Leaflet Init
            const map = L.map('radar-map', {
                zoomControl: false,
            }).setView([-2.5489, 118.0149], 5); // Default to center of Indonesia
            
            L.control.zoom({ position: 'bottomright' }).addTo(map);

            // Use a clean, modern basemap like CartoDB Positron or Voyager
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap &copy; CARTO'
            }).addTo(map);

            const markers = {};
            const onlineMembersContainer = document.getElementById('online-members');
            const currentUserId = {{ Auth::id() }};
            let watchId = null;

            // Generate custom marker HTML
            function getMarkerHtml(initials, colorClass) {
                return `
                    <div class="relative w-10 h-10 -ml-5 -mt-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full ${colorClass} flex items-center justify-center text-white font-bold text-sm shadow-elevation-2 border-2 border-white z-10 relative">
                            ${initials}
                        </div>
                        <div class="w-3 h-3 ${colorClass} rotate-45 -mt-2 border-r-2 border-b-2 border-white"></div>
                    </div>
                `;
            }

            // Update sidebar UI
            function updateOnlineMembersUI(members) {
                onlineMembersContainer.innerHTML = '';
                if(members.length === 0) {
                    onlineMembersContainer.innerHTML = '<p class="font-label text-[11px] text-outline">Menunggu anggota lain bergabung...</p>';
                    return;
                }
                
                members.forEach(member => {
                    const isMe = member.id === currentUserId;
                    onlineMembersContainer.innerHTML += `
                        <div class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-low transition-all" id="member-card-${member.id}">
                            <div class="w-8 h-8 rounded-full ${isMe ? 'bg-primary' : 'bg-secondary'} flex items-center justify-center text-white font-bold text-[11px] shadow-sm">${member.initials}</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-label text-[12px] text-on-surface truncate">${member.name} ${isMe ? '(Anda)' : ''}</p>
                                <p class="font-label text-[10px] text-outline flex items-center gap-1" id="member-status-${member.id}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Online
                                </p>
                            </div>
                        </div>
                    `;
                });
            }

            // Handle map markers
            function updateMemberLocation(id, initials, lat, lng, isMe) {
                if (!markers[id]) {
                    const icon = L.divIcon({
                        className: 'custom-div-icon',
                        html: getMarkerHtml(initials, isMe ? 'bg-primary' : 'bg-secondary'),
                        iconSize: [40, 40],
                        iconAnchor: [20, 40]
                    });
                    markers[id] = L.marker([lat, lng], {icon: icon}).addTo(map);
                    
                    if (isMe) {
                        map.setView([lat, lng], 15);
                    }
                } else {
                    markers[id].setLatLng([lat, lng]);
                }
            }

            function removeMemberLocation(id) {
                if (markers[id]) {
                    map.removeLayer(markers[id]);
                    delete markers[id];
                }
            }

            // --- Reverb Presence Channel Logic ---
            let activeMembers = [];
            
            // Wait for Echo to be ready
            let echoCheckAttempts = 0;
            const initEcho = setInterval(() => {
                if (typeof window.Echo !== 'undefined') {
                    clearInterval(initEcho);
                    connectToRadar();
                } else if(echoCheckAttempts > 20) { // 10 seconds max
                    clearInterval(initEcho);
                    console.error("Laravel Echo is not initialized. Make sure reverb is running and assets are built.");
                    onlineMembersContainer.innerHTML = '<p class="font-label text-[11px] text-error">Gagal menyambung ke server radar.</p>';
                }
                echoCheckAttempts++;
            }, 500);

            function connectToRadar() {
                const channel = window.Echo.join('trip.{{ $trip->id }}');

                channel.here((users) => {
                    activeMembers = users;
                    updateOnlineMembersUI(activeMembers);
                    startBroadcasting(channel);
                })
                .joining((user) => {
                    activeMembers.push(user);
                    updateOnlineMembersUI(activeMembers);
                })
                .leaving((user) => {
                    activeMembers = activeMembers.filter(u => u.id !== user.id);
                    updateOnlineMembersUI(activeMembers);
                    removeMemberLocation(user.id);
                })
                .listenForWhisper('location', (e) => {
                    // Update location for other user
                    const user = activeMembers.find(u => u.id === e.id);
                    if (user) {
                        updateMemberLocation(e.id, user.initials, e.lat, e.lng, false);
                    }
                });
            }

            function startBroadcasting(channel) {
                if ("geolocation" in navigator) {
                    watchId = navigator.geolocation.watchPosition((position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Update my own marker
                        const me = activeMembers.find(u => u.id === currentUserId);
                        if (me) {
                            updateMemberLocation(me.id, me.initials, lat, lng, true);
                        }

                        // Broadcast to others
                        channel.whisper('location', {
                            id: currentUserId,
                            lat: lat,
                            lng: lng
                        });
                    }, (error) => {
                        console.warn("Geolocation error:", error);
                        if (error.code === error.PERMISSION_DENIED) {
                            onlineMembersContainer.innerHTML = '<p class="font-label text-[11px] text-error">Mohon izinkan akses lokasi (GPS) di browser Anda untuk membagikan posisi.</p>';
                        }
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 0,
                        timeout: 10000
                    });
                } else {
                    alert("Browser Anda tidak mendukung Geolocation.");
                }
            }
            
            // Cleanup on page leave
            window.addEventListener('beforeunload', () => {
                if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                if (typeof window.Echo !== 'undefined') window.Echo.leave('trip.{{ $trip->id }}');
            });
        });
    </script>
    <style>
        .custom-div-icon {
            background: transparent;
            border: none;
        }
    </style>
</x-app-layout>
