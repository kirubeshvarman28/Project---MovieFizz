// === MovieFizz Frontend JS ===

// 1. Header scroll effect
const header = document.getElementById('mainHeader');
if (header) {
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 50);
    });
    // Set initial state
    if (window.scrollY > 50) header.classList.add('scrolled');
}

// 2. AJAX Search (movies + TV shows)
const searchInput = document.getElementById('ajax_search');
const searchResults = document.getElementById('search_results');
let searchTimeout;

if (searchInput && searchResults) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        
        if (q.length < 2) {
            searchResults.classList.remove('active');
            searchResults.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`search_ajax.php?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        searchResults.innerHTML = `
                            <div style="padding:25px; text-align:center;">
                                <div style="color:#808080; margin-bottom:15px;">No results found for "${q}"</div>
                                <button type="button" id="openRequestBtn" class="btn btn-primary btn-sm" style="background:var(--primary); color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; font-weight:600;">
                                    <i class="fas fa-plus-circle"></i> Request a Movie or TV Show
                                </button>
                            </div>
                        `;
                        searchResults.classList.add('active');
                        
                        // Add listener to the new button
                        document.getElementById('openRequestBtn')?.addEventListener('click', () => {
                            const modal = document.getElementById('requestModal');
                            if (modal) {
                                modal.style.display = 'flex';
                                modal.classList.add('active');
                                document.getElementById('req_title').value = q;
                            }
                        });
                        return;
                    }

                    searchResults.innerHTML = data.map(item => `
                        <a href="${item.type === 'show' ? 'show' : 'movie'}.php?id=${item.id}" class="search-result-item">
                            <img src="${item.poster}" alt="${item.title}" onerror="this.src='../assets/images/no-poster.png'">
                            <div class="sr-info">
                                <h4>${item.title}</h4>
                                <span>${item.year || ''} ${item.type === 'show' ? '• TV Show' : '• Movie'}</span>
                            </div>
                        </a>
                    `).join('');
                    searchResults.classList.add('active');
                })
                .catch(err => console.error('Search error:', err));
        }, 300);
    });

    // Close search on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            searchResults.classList.remove('active');
            document.getElementById('searchBox')?.classList.remove('open');
        }
    });
}

// 3. Carousel scroll buttons
document.querySelectorAll('.carousel-wrapper').forEach(wrapper => {
    const track = wrapper.querySelector('.carousel-track');
    const leftBtn = wrapper.querySelector('.carousel-btn.left');
    const rightBtn = wrapper.querySelector('.carousel-btn.right');

    if (leftBtn) {
        leftBtn.addEventListener('click', () => {
            track.scrollBy({ left: -track.offsetWidth * 0.75, behavior: 'smooth' });
        });
    }
    if (rightBtn) {
        rightBtn.addEventListener('click', () => {
            track.scrollBy({ left: track.offsetWidth * 0.75, behavior: 'smooth' });
        });
    }
});

// 4. Watchlist toggle
const watchlistBtn = document.getElementById('watchlist_btn');
if (watchlistBtn) {
    watchlistBtn.addEventListener('click', function() {
        const movieId = this.getAttribute('data-id');
        fetch('watchlist_ajax.php?movie_id=' + movieId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'added') {
                    this.innerHTML = '<i class="fas fa-check"></i> In My List';
                    this.classList.add('in-list');
                } else if (data.status === 'removed') {
                    this.innerHTML = '<i class="fas fa-plus"></i> My List';
                    this.classList.remove('in-list');
                } else {
                    alert(data.message || 'Please sign in');
                }
            });
    });
}

// 5. Dynamic Hero Slider
function initHeroSlider() {
    const activeClass = 'active';
    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.indicator');
    if (slides.length <= 1) return;

    let currentIndex = 0;
    let slideInterval;

    const showSlide = (index) => {
        slides.forEach(s => s.classList.remove(activeClass));
        indicators.forEach(i => i.classList.remove(activeClass));
        
        slides[index].classList.add(activeClass);
        if (indicators[index]) indicators[index].classList.add(activeClass);
        currentIndex = index;
    };

    const nextSlide = () => {
        let next = (currentIndex + 1) % slides.length;
        showSlide(next);
    };

    const startInterval = () => {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 10000); // 10 seconds
    };

    indicators.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.getAttribute('data-index'));
            showSlide(index);
            startInterval(); // Reset timer on click
        });
    });

    startInterval();
}
document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();

    // 6. User menu dropdown toggle
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-menu')) {
                userMenu.classList.remove('active');
            }
        });
    }

    // 7. Netflix-Style Player Logic
    initNetflixPlayer();
});

function initNetflixPlayer() {
    const player = document.querySelector('.netflix-player');
    if (!player) return;

    const video = player.querySelector('video');
    const playBtn = player.querySelector('.play-btn');
    const skipBack = player.querySelector('.skip-back');
    const skipForward = player.querySelector('.skip-forward');
    const volumeInput = player.querySelector('.volume-slider input');
    const volumeBtn = player.querySelector('.volume-btn');
    const progressContainer = player.querySelector('.progress-container');
    const progressBar = player.querySelector('.progress-current');
    const timeDisplay = player.querySelector('.time-display');
    const fullscreenBtn = player.querySelector('.full-screen-btn');
    const menuBtn = player.querySelector('.settings-btn');
    const menu = player.querySelector('.audio-subs-menu');
    const exitBtn = player.querySelector('.player-exit-btn');

    // Controls visibility
    let controlsTimer;
    const showControls = () => {
        player.classList.add('active');
        clearTimeout(controlsTimer);
        controlsTimer = setTimeout(() => {
            if (video && !video.paused) player.classList.remove('active');
            else if (!video) player.classList.remove('active');
        }, 3000);
    };

    player.addEventListener('mousemove', showControls);
    player.addEventListener('click', showControls);

    // Play / Pause
    const togglePlay = () => {
        if (!video) return;
        if (video.paused) {
            video.play();
            playBtn.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            video.pause();
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
        }
    };

    if (playBtn) playBtn.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        initAudioContext();
        togglePlay(); 
    });
    if (video) video.addEventListener('click', () => {
        initAudioContext();
        togglePlay();
    });

    // Skip (Removed as requested, using Next Episode button instead)
    /*
    if (skipBack) skipBack.addEventListener('click', (e) => {
        e.stopPropagation();
        if (video) video.currentTime -= 10;
    });
    if (skipForward) skipForward.addEventListener('click', (e) => {
        e.stopPropagation();
        if (video) video.currentTime += 10;
    });
    */

    // Progress Bar
    if (video) {
        video.addEventListener('timeupdate', () => {
            const percent = (video.currentTime / video.duration) * 100;
            if (progressBar) progressBar.style.width = percent + '%';
            
            // Time display
            const formatTime = (time) => {
                const h = Math.floor(time / 3600);
                const m = Math.floor((time % 3600) / 60);
                const s = Math.floor(time % 60);
                return (h > 0 ? h + ':' : '') + (m < 10 && h > 0 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            };
            if (timeDisplay) timeDisplay.innerText = `${formatTime(video.currentTime)} / ${formatTime(video.duration)}`;
        });
    }

    if (progressContainer) progressContainer.addEventListener('click', (e) => {
        if (!video || !video.duration) return;
        const rect = progressContainer.getBoundingClientRect();
        const pos = (e.pageX - rect.left) / rect.width;
        video.currentTime = pos * video.duration;
    });

    // 7.1 Web Audio API "Volume Boost" (0-200%)
    let audioCtx = null;
    let gainNode = null;
    let sourceNode = null;

    const initAudioContext = () => {
        if (!video || audioCtx) return;
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            gainNode = audioCtx.createGain();
            sourceNode = audioCtx.createMediaElementSource(video);
            sourceNode.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            gainNode.gain.value = volumeInput.value;
        } catch (e) {
            console.error('Web Audio API not supported:', e);
        }
    };

    // Volume
    volumeInput.max = 2.0; // Allow boost up to 200%
    volumeInput.addEventListener('input', (e) => {
        const val = parseFloat(e.target.value);
        if (video) video.volume = Math.min(val, 1.0); // Native max is 1.0
        
        if (gainNode) {
            gainNode.gain.value = val;
        }
        
        if (window.activeAltAudio) {
            window.activeAltAudio.volume = Math.min(val, 1.0);
            if (window.altGainNode) window.altGainNode.gain.value = val;
        }

        // Visual feedback for boost
        if (val > 1.0) {
            volumeBtn.innerHTML = '<i class="fas fa-volume-up text-warning"></i>';
            volumeBtn.title = `Volume Boost: ${Math.round(val * 100)}%`;
        } else if (val == 0) {
            volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        } else {
            volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    });

    // Fullscreen
    fullscreenBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!document.fullscreenElement) {
            player.requestFullscreen().catch(err => console.log(err));
        } else {
            document.exitFullscreen();
        }
    });

    // Audio / Subs Menu
    menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('active');
    });

    // Quality / Resolution logic
    const resList = player.querySelector('#menuResList');
    const sourceBtns = document.querySelectorAll('.source-btn');
    const mediaContainer = player.querySelector('#mediaContainer');
    
    const switchSource = (url, type, btn) => {
        // Update active states
        sourceBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Handle iframe vs video
        if (type === 'embed' || type === 'cloud' || type === 'iframe' || url.includes('<iframe') || url.includes('youtube.com') || url.includes('youtu.be')) {
            let iframeSrc = url;
            if (url.includes('youtube.com/watch?v=')) {
                const vid = url.split('v=')[1].split('&')[0];
                iframeSrc = `https://www.youtube.com/embed/${vid}`;
            } else if (url.includes('youtu.be/')) {
                const vid = url.split('.be/')[1].split('?')[0];
                iframeSrc = `https://www.youtube.com/embed/${vid}`;
            } else if (url.includes('<iframe')) {
                const match = url.match(/src="([^"]+)"/);
                iframeSrc = match ? match[1] : url;
            }
            mediaContainer.innerHTML = `<iframe src="${iframeSrc}" style="width:100%; height:100%; border:none;" allowfullscreen allow="autoplay; encrypted-media"></iframe>`;
        } else {
            // It's a video file or direct URL
            // Ensure we have a video element (it might have been replaced by an iframe)
            let videoElem = mediaContainer.querySelector('video');
            if (!videoElem) {
                mediaContainer.innerHTML = `<video id="mainVideo" poster="${video.poster}" preload="metadata" style="width:100%; height:100%; object-fit:contain;"></video>`;
                videoElem = mediaContainer.querySelector('video');
                // Re-init listeners for the new video element if needed
                // But for simplicity, we'll try to just update the existing one if it exists
            }
            videoElem.src = url;
            videoElem.load();
            videoElem.play().then(() => {
                showControls();
            }).catch(e => {
                console.log("Playback failed:", e);
                if (e.name === 'NotAllowedError') {
                    videoElem.muted = true;
                    videoElem.play();
                }
            });
        }
        
        // Update menu items active state
        const menuItems = player.querySelectorAll('#menuResList .menu-item');
        menuItems.forEach((item, idx) => {
            item.classList.remove('active');
            if (sourceBtns[idx] === btn) item.classList.add('active');
        });
        menu.classList.remove('active');
    };

    if (sourceBtns.length > 0) {
        resList.innerHTML = '';
        sourceBtns.forEach((btn, idx) => {
            const url = btn.getAttribute('data-url');
            const type = btn.getAttribute('data-type');
            
            // Add to Resolution Menu
            const item = document.createElement('div');
            item.className = 'menu-item' + (btn.classList.contains('active') ? ' active' : '');
            item.innerText = btn.innerText;
            item.onclick = (e) => {
                e.stopPropagation();
                switchSource(url, type, btn);
            };
            resList.appendChild(item);

            // Also hijack the original button if it's still clickable
            btn.onclick = (e) => {
                e.preventDefault();
                switchSource(url, type, btn);
            };
        });
    }

    // Settings Menu Tab Switching
    const tabs = menu.querySelectorAll('.menu-tab');
    const lists = menu.querySelectorAll('.menu-list');
    if (tabs.length > 0) {
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.stopPropagation();
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                lists.forEach(l => l.style.display = 'none');

                tab.classList.add('active');
                const targetList = menu.querySelector(`#menu${target.charAt(0).toUpperCase() + target.slice(1)}List`);
                if (targetList) targetList.style.display = 'block';
            });
        });
    }

    // Auto-detect tracks (Audio & Subtitles)
    const updateTracks = () => {
        const videoElem = player.querySelector('video');
        if (!videoElem) return;

        // 1. Audio Tracks (Dual-Media Sync via Hidden Tags)
        const audioList = player.querySelector('#menuAudioList');
        const altAudios = player.querySelectorAll('.alt-audio-track');
        
        const mainAudioLabel = video.getAttribute('data-main-audio-label') || 'Original Audio';
        audioList.innerHTML = `<div class="menu-item active" data-index="default">${mainAudioLabel}</div>`;
        
        if (altAudios.length > 0) {
            altAudios.forEach((aud) => {
                const item = document.createElement('div');
                item.className = 'menu-item';
                item.innerText = aud.dataset.label;
                item.onclick = (e) => {
                    e.stopPropagation();
                    switchAudio(aud);
                };
                audioList.appendChild(item);
            });
        }

        audioList.querySelector('[data-index="default"]').onclick = (e) => {
            e.stopPropagation();
            switchAudio(null);
        };

        // 2. Subtitles (Text Tracks)
        const subsList = player.querySelector('#menuSubsList');
        if (videoElem.textTracks) {
            subsList.innerHTML = '<div class="menu-item active" data-index="none">Off</div>';
            let anyShowing = false;
            for (let i = 0; i < videoElem.textTracks.length; i++) {
                const track = videoElem.textTracks[i];
                // Only include subtitles and captions
                if (track.kind === 'subtitles' || track.kind === 'captions' || track.kind === 'metadata') {
                    const item = document.createElement('div');
                    item.className = 'menu-item' + (track.mode === 'showing' ? ' active' : '');
                    if (track.mode === 'showing') anyShowing = true;
                    item.innerText = track.label || track.language || 'Subtitle ' + (i + 1);
                    item.onclick = (e) => {
                        e.stopPropagation();
                        switchTrack(i);
                    };
                    subsList.appendChild(item);
                }
            }
            if (anyShowing) subsList.querySelector('[data-index="none"]').classList.remove('active');
            
            subsList.querySelector('[data-index="none"]').onclick = (e) => {
                e.stopPropagation();
                switchTrack('none');
            };
        }
    };

    if (video) {
        video.addEventListener('loadedmetadata', updateTracks);
        
        // Auto-Sync Events for Alternative Audio
        video.addEventListener('play', () => { if (window.activeAltAudio) window.activeAltAudio.play(); });
        video.addEventListener('pause', () => { if (window.activeAltAudio) window.activeAltAudio.pause(); });
        video.addEventListener('seeked', () => { 
            if (window.activeAltAudio) window.activeAltAudio.currentTime = video.currentTime; 
        });
        video.addEventListener('ratechange', () => { 
            if (window.activeAltAudio) window.activeAltAudio.playbackRate = video.playbackRate; 
        });
        video.addEventListener('timeupdate', () => {
            if (window.activeAltAudio && !window.activeAltAudio.paused && !video.paused) {
                const diff = Math.abs(window.activeAltAudio.currentTime - video.currentTime);
                if (diff > 0.3) window.activeAltAudio.currentTime = video.currentTime;
            }
        });
    }

    function switchAudio(newAudioElem) {
        const videoElem = player.querySelector('video');
        if (!videoElem) return;

        if (window.activeAltAudio && window.activeAltAudio !== newAudioElem) {
            window.activeAltAudio.pause();
        }

        window.activeAltAudio = newAudioElem;

        const audioItems = player.querySelectorAll('#menuAudioList .menu-item');
        audioItems.forEach(item => item.classList.remove('active'));
        
        if (newAudioElem === null) {
            audioItems[0].classList.add('active');
            videoElem.muted = false; // Restore original audio
        } else {
            const altAudios = player.querySelectorAll('.alt-audio-track');
            const index = Array.from(altAudios).indexOf(newAudioElem);
            if (audioItems[index + 1]) audioItems[index + 1].classList.add('active');
            
            // Connect Alt Audio to Web Audio API for Boost
            if (audioCtx && !newAudioElem.sourceConnected) {
                const altSource = audioCtx.createMediaElementSource(newAudioElem);
                if (!window.altGainNode) {
                    window.altGainNode = audioCtx.createGain();
                    window.altGainNode.connect(audioCtx.destination);
                }
                altSource.connect(window.altGainNode);
                window.altGainNode.gain.value = volumeInput.value;
                newAudioElem.sourceConnected = true;
            }

            videoElem.muted = true; // Mute original audio
            newAudioElem.currentTime = videoElem.currentTime;
            newAudioElem.volume = Math.min(volumeInput.value, 1.0);
            if (!videoElem.paused) newAudioElem.play().catch(e => console.log("Audio Play Error:", e));
        }
    }

    function switchTrack(index) {
        const videoElem = player.querySelector('video');
        if (!videoElem) return;

        for (let i = 0; i < videoElem.textTracks.length; i++) {
            videoElem.textTracks[i].mode = (i === index) ? 'showing' : 'hidden';
        }
        
        const subsItems = player.querySelectorAll('#menuSubsList .menu-item');
        subsItems.forEach((item, i) => {
            item.classList.remove('active');
            if (index === 'none' && i === 0) item.classList.add('active');
            else if (i === index + 1) item.classList.add('active');
        });
    }

    // Exit
    if (exitBtn) {
        exitBtn.addEventListener('click', () => {
            if (document.referrer && document.referrer.includes(window.location.hostname)) {
                window.location.href = document.referrer;
            } else {
                window.location.href = 'index.php';
            }
        });
    }

    // Keyboard controls
    document.addEventListener('keydown', (e) => {
        const videoElem = player.querySelector('video');
        if (!videoElem) return;
        
        if (e.code === 'Space') { e.preventDefault(); togglePlay(); }
        if (e.code === 'ArrowRight') videoElem.currentTime += 10;
        if (e.code === 'ArrowLeft') videoElem.currentTime -= 10;
        if (e.code === 'KeyF') fullscreenBtn.click();
        if (e.code === 'KeyM') volumeBtn.click();
    });
}

// 8. Media Request Modal Controls
const requestModal = document.getElementById('requestModal');
const closeRequestModal = document.getElementById('closeRequestModal');
const closeSuccessBtn = document.getElementById('closeSuccessBtn');
const mediaRequestForm = document.getElementById('mediaRequestForm');

if (requestModal) {
    const closeModal = () => {
        requestModal.style.display = 'none';
        requestModal.classList.remove('active');
        // Reset form after delay
        setTimeout(() => {
            document.getElementById('requestFormContent').style.display = 'block';
            document.getElementById('requestSuccessContent').style.display = 'none';
            mediaRequestForm?.reset();
        }, 300);
    };

    closeRequestModal?.addEventListener('click', closeModal);
    closeSuccessBtn?.addEventListener('click', closeModal);
    
    requestModal.addEventListener('click', (e) => {
        if (e.target === requestModal) closeModal();
    });

    // Handle Form Submission
    mediaRequestForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitRequestBtn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        const formData = new FormData(this);
        fetch('request_media.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('requestFormContent').style.display = 'none';
                document.getElementById('requestSuccessContent').style.display = 'block';
            } else {
                alert(data.message || 'Something went wrong.');
            }
        })
        .catch(err => {
            console.error('Request error:', err);
            alert('Connection failed. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}
