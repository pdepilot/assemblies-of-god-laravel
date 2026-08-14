/**
 * AGC Ikenegbu — Sermon Library + SermonAudioManager
 */

(function () {
    "use strict";

    var API_BASE = "../api/sermon-api.php";
    var THUMBS = ["thumb-v1", "thumb-v2", "thumb-v3", "thumb-v4"];

    var DEFAULT_IMAGE = "../images/church1.webp";

    var CATEGORIES = ["All"];
    var SERMONS = [];
    var SERIES = [];
    var SPEAKERS = [];
    var USE_API = true;
    var apiState = { liveStream: null, liveAudio: null, upcomingStream: null, countdownTimer: null, sse: null, hybridMode: 'video', listenerSession: '' };

    function mapApiSermon(row) {
        var id = row.id;
        return {
            id: id,
            title: row.title,
            speaker: row.minister_name || "Minister",
            category: row.category || "Teaching",
            categorySlug: row.category_slug || "",
            date: row.sermon_date,
            duration: row.duration || "0:00",
            views: row.view_count || 0,
            desc: row.description || "",
            featured: !!row.is_featured,
            trending: !!row.is_trending,
            thumb: THUMBS[(id - 1) % THUMBS.length],
            image: row.featured_image_url || DEFAULT_IMAGE,
            audioUrl: row.audio_url || "",
            sermonType: row.sermon_type || "audio",
            slug: row.slug || "",
            embedHtml: row.embed_html || "",
            pdfUrl: row.pdf_url || "",
            shareUrls: row.share_urls || {}
        };
    }

    function loadFromApi() {
        if (!USE_API) return Promise.resolve();
        var q = encodeURIComponent(state.query || "");
        var cat = state.category === "All" ? "" : encodeURIComponent(state.categorySlug || state.category.toLowerCase().replace(/\s+/g, "_"));
        var url = API_BASE + "?action=bootstrap&page=" + state.page + "&per_page=" + PER_PAGE + "&q=" + q + "&category=" + cat;

        return fetch(url, { credentials: "same-origin" })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) throw new Error("API unavailable");
                apiState.liveStream = data.live_stream;
                apiState.liveAudio = data.live_audio;
                apiState.upcomingStream = data.upcoming_stream;
                renderLiveSections(data);
                updateWatchLiveNav(!!data.show_watch_live_nav);
                updateListenLiveNav(!!data.show_listen_live_nav);

                if (data.categories && data.categories.length) {
                    CATEGORIES = data.categories.map(function (c) { return c.name; });
                    state.categorySlugs = {};
                    data.categories.forEach(function (c) { state.categorySlugs[c.name] = c.slug; });
                }

                if (data.series && data.series.length) {
                    SERIES = data.series.map(function (s, i) {
                        return {
                            id: s.slug || ("s" + s.id),
                            title: s.title,
                            count: s.sermon_count || 0,
                            speaker: s.minister_name || "",
                            thumb: THUMBS[i % THUMBS.length],
                            image: s.cover_image_url || DEFAULT_IMAGE
                        };
                    });
                } else {
                    SERIES = [];
                }

                if (data.speakers && data.speakers.length) {
                    SPEAKERS = data.speakers.map(function (sp) {
                        return { name: sp.name, role: "Minister", count: sp.count || 0 };
                    });
                } else {
                    SPEAKERS = [];
                }

                var items = (data.sermons && data.sermons.items) ? data.sermons.items : [];
                if (items.length) {
                    SERMONS = items.map(mapApiSermon);
                    state.apiTotal = data.sermons.total || items.length;
                    state.apiPages = data.sermons.pages || 1;
                } else {
                    SERMONS = [];
                    state.apiTotal = 0;
                    state.apiPages = 1;
                }

                if (data.featured_sermon) {
                    window.__featuredOverride = mapApiSermon(data.featured_sermon);
                } else {
                    window.__featuredOverride = null;
                }
            })
            .catch(function () {
                SERMONS = [];
                SERIES = [];
                SPEAKERS = [];
                state.apiTotal = 0;
                state.apiPages = 1;
                window.__featuredOverride = null;
            });
    }

    function updateWatchLiveNav(show) {
        var link = document.getElementById("navWatchLive");
        if (link) link.classList.toggle("is-hidden", !show);
    }

    function updateListenLiveNav(show) {
        var link = document.getElementById("navListenLive");
        if (link) link.classList.toggle("is-hidden", !show);
    }

    function renderShareButtons(containerId, share) {
        var el = document.getElementById(containerId);
        if (!el || !share) return;
        el.innerHTML =
            (share.facebook ? '<a href="' + escapeHtml(share.facebook) + '" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>' : "") +
            (share.whatsapp ? '<a href="' + escapeHtml(share.whatsapp) + '" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>' : "") +
            (share.twitter ? '<a href="' + escapeHtml(share.twitter) + '" target="_blank" rel="noopener" aria-label="Share on X"><i class="fab fa-twitter"></i></a>' : "") +
            (share.telegram ? '<a href="' + escapeHtml(share.telegram) + '" target="_blank" rel="noopener" aria-label="Share on Telegram"><i class="fab fa-telegram"></i></a>' : "");
    }

    function pingViewer(streamId) {
        if (!streamId) return;
        fetch(API_BASE + "?action=viewer_ping", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ stream_id: streamId, session_key: apiState.listenerSession || "" })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.viewer_count != null) {
                var el = document.getElementById("liveViewerCount");
                if (el) el.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i> ' + Number(d.viewer_count).toLocaleString() + " watching";
            }
        }).catch(function () {});
    }

    function pingListener(streamId) {
        if (!streamId) return;
        if (!apiState.listenerSession) {
            apiState.listenerSession = "ls_" + Math.random().toString(36).slice(2) + Date.now();
        }
        fetch(API_BASE + "?action=listener_ping", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ stream_id: streamId, session_key: apiState.listenerSession })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.listener_count != null) {
                var el = document.getElementById("liveListenerCount");
                if (el) el.innerHTML = '<i class="fas fa-headphones" aria-hidden="true"></i> ' + Number(d.listener_count).toLocaleString() + " listening";
            }
        }).catch(function () {});
    }

    function LiveAudioBroadcastManager() {
        this.audio = new Audio();
        this.audio.crossOrigin = "anonymous";
        this.streamId = null;
        this.playBtn = document.getElementById("liveAudioPlay");
        this.pauseBtn = document.getElementById("liveAudioPause");
        this.muteBtn = document.getElementById("liveAudioMute");
        this.volume = document.getElementById("liveAudioVolume");
        this.wave = document.getElementById("liveAudioWave");
        var self = this;

        if (this.playBtn) this.playBtn.addEventListener("click", function () { self.play(); });
        if (this.pauseBtn) this.pauseBtn.addEventListener("click", function () { self.pause(); });
        if (this.muteBtn) this.muteBtn.addEventListener("click", function () { self.toggleMute(); });
        if (this.volume) this.volume.addEventListener("input", function () { self.setVolume(Number(self.volume.value)); });

        this.audio.addEventListener("playing", function () {
            if (self.wave) self.wave.classList.remove("is-paused");
            if (self.playBtn) self.playBtn.disabled = true;
            if (self.pauseBtn) self.pauseBtn.disabled = false;
        });
        this.audio.addEventListener("pause", function () {
            if (self.wave) self.wave.classList.add("is-paused");
            if (self.playBtn) self.playBtn.disabled = false;
            if (self.pauseBtn) self.pauseBtn.disabled = true;
        });
        this.setVolume(this.volume ? Number(this.volume.value) : 80);
    }

    LiveAudioBroadcastManager.prototype.load = function (broadcast) {
        if (!broadcast || !broadcast.audio_stream_url) return;
        if (this.streamId === broadcast.id && this.audio.src === broadcast.audio_stream_url) return;
        this.stop();
        this.streamId = broadcast.id;
        this.audio.src = broadcast.audio_stream_url;
        this.audio.load();
        pingListener(broadcast.id);
    };

    LiveAudioBroadcastManager.prototype.play = function () {
        var self = this;
        this.audio.play().catch(function () {});
        if (this.streamId) pingListener(this.streamId);
    };

    LiveAudioBroadcastManager.prototype.pause = function () {
        this.audio.pause();
    };

    LiveAudioBroadcastManager.prototype.stop = function () {
        this.audio.pause();
        this.audio.removeAttribute("src");
        this.audio.load();
        this.streamId = null;
    };

    LiveAudioBroadcastManager.prototype.setVolume = function (val) {
        this.audio.volume = Math.max(0, Math.min(1, val / 100));
        if (this.muteBtn) {
            var icon = this.muteBtn.querySelector("i");
            if (icon) icon.className = this.audio.volume === 0 ? "fas fa-volume-mute" : "fas fa-volume-up";
        }
    };

    LiveAudioBroadcastManager.prototype.toggleMute = function () {
        if (this.audio.volume > 0) {
            this._prevVol = this.audio.volume;
            this.setVolume(0);
            if (this.volume) this.volume.value = "0";
        } else {
            var v = this._prevVol != null ? this._prevVol * 100 : 80;
            if (this.volume) this.volume.value = String(Math.round(v));
            this.setVolume(v);
        }
    };

    var liveAudioManager = null;

    function getLiveAudioManager() {
        if (!liveAudioManager) liveAudioManager = new LiveAudioBroadcastManager();
        return liveAudioManager;
    }

    function bindHybridToggle(isHybrid) {
        var toggle = document.getElementById("liveHybridToggle");
        var videoSec = document.getElementById("liveNowSection");
        var audioSec = document.getElementById("liveAudioSection");
        if (!toggle) return;
        toggle.classList.toggle("is-hidden", !isHybrid);
        if (!isHybrid) return;

        toggle.querySelectorAll(".live-mode-btn").forEach(function (btn) {
            btn.onclick = function () {
                apiState.hybridMode = btn.getAttribute("data-mode") || "video";
                toggle.querySelectorAll(".live-mode-btn").forEach(function (b) {
                    b.classList.toggle("is-active", b === btn);
                });
                if (apiState.hybridMode === "audio") {
                    if (videoSec) videoSec.classList.add("is-hidden");
                    if (audioSec) audioSec.classList.remove("is-hidden");
                    if (apiState.liveAudio) {
                        getLiveAudioManager().load(apiState.liveAudio);
                        renderLiveAudioPlatforms(apiState.liveAudio, getLiveAudioManager());
                    }
                } else {
                    if (videoSec) videoSec.classList.remove("is-hidden");
                    if (audioSec) audioSec.classList.add("is-hidden");
                    getLiveAudioManager().pause();
                }
            };
        });
    }

    function renderLiveVideoPlayer(live) {
        var wrap = document.getElementById("livePlayerWrap");
        if (!wrap || !live) return;
        var platforms = (live.platforms || []).filter(function (p) {
            return p.embed_html || p.video_stream_url || p.embed_url;
        });
        if (platforms.length <= 1) {
            wrap.innerHTML = live.embed_html || (platforms[0] && platforms[0].embed_html) || "";
            return;
        }
        var active = platforms.find(function (p) { return p.is_primary; }) || platforms[0];
        var tabs = platforms.map(function (p, i) {
            var isActive = p.platform === active.platform;
            return '<button type="button" class="live-platform-btn' + (isActive ? " is-active" : "") + '" data-platform-index="' + i + '">' + escapeHtml(p.platform_label || p.platform) + "</button>";
        }).join("");
        wrap.innerHTML = '<div class="live-platform-tabs" role="tablist" aria-label="Watch live platform">' + tabs + '</div><div class="live-platform-player" id="livePlatformPlayer">' + (active.embed_html || "") + "</div>";
        wrap.querySelectorAll(".live-platform-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var idx = parseInt(btn.getAttribute("data-platform-index"), 10);
                var plat = platforms[idx];
                if (!plat) return;
                wrap.querySelectorAll(".live-platform-btn").forEach(function (b) {
                    b.classList.toggle("is-active", b === btn);
                });
                var player = document.getElementById("livePlatformPlayer");
                if (player) player.innerHTML = plat.embed_html || "";
            });
        });
    }

    function renderLiveAudioPlatforms(audio, mgr) {
        var bar = document.getElementById("liveAudioPlatforms");
        if (!bar) return;
        var platforms = (audio.platforms || []).filter(function (p) { return p.audio_stream_url; });
        if (platforms.length <= 1) {
            bar.innerHTML = "";
            bar.classList.add("is-hidden");
            mgr.load(audio);
            return;
        }
        bar.classList.remove("is-hidden");
        var active = platforms.find(function (p) { return p.is_primary; }) || platforms[0];
        bar.innerHTML = platforms.map(function (p, i) {
            var isActive = p.platform === active.platform;
            return '<button type="button" class="live-platform-btn live-platform-btn--sm' + (isActive ? " is-active" : "") + '" data-audio-platform="' + i + '">' + escapeHtml(p.platform_label || p.platform) + "</button>";
        }).join("");
        mgr.load(Object.assign({}, audio, { audio_stream_url: active.audio_stream_url }));
        bar.querySelectorAll("[data-audio-platform]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var idx = parseInt(btn.getAttribute("data-audio-platform"), 10);
                var plat = platforms[idx];
                if (!plat) return;
                bar.querySelectorAll("[data-audio-platform]").forEach(function (b) {
                    b.classList.toggle("is-active", b === btn);
                });
                mgr.load(Object.assign({}, audio, { audio_stream_url: plat.audio_stream_url }));
            });
        });
    }

    function renderLiveSections(data) {
        var liveSec = document.getElementById("liveNowSection");
        var audioSec = document.getElementById("liveAudioSection");
        var upSec = document.getElementById("upcomingStreamSection");
        var live = data.live_stream;
        var audio = data.live_audio;
        var upcoming = data.upcoming_stream;
        var mgr = getLiveAudioManager();

        if (live || audio) {
            if (upSec) upSec.classList.add("is-hidden");
        }

        var isHybrid = live && audio && live.is_hybrid && live.id === audio.id;
        bindHybridToggle(!!isHybrid);

        if (live && liveSec && (!isHybrid || apiState.hybridMode !== "audio")) {
            liveSec.classList.remove("is-hidden");
            document.getElementById("liveNowTitle").textContent = live.title || "Live Service";
            document.getElementById("liveMinister").textContent = live.minister_name || "";
            document.getElementById("liveDescription").textContent = live.description || "";
            document.getElementById("liveViewerCount").innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i> ' + (live.viewer_count || 0).toLocaleString() + " watching";
            renderLiveVideoPlayer(live);
            renderShareButtons("liveShareBtns", live.share_urls);
            pingViewer(live.id);
        } else if (liveSec && !isHybrid) {
            liveSec.classList.add("is-hidden");
        }

        if (audio && audioSec) {
            var showAudio = !live || audio.broadcast_type === "audio" || (isHybrid && apiState.hybridMode === "audio");
            audioSec.classList.toggle("is-hidden", !showAudio);
            if (showAudio) {
                document.getElementById("liveAudioTitle").textContent = audio.title || "Live Audio";
                document.getElementById("liveAudioMinister").textContent = audio.minister_name || "";
                document.getElementById("liveListenerCount").innerHTML = '<i class="fas fa-headphones" aria-hidden="true"></i> ' + (audio.listener_count || 0).toLocaleString() + " listening";
                var thumb = document.getElementById("liveAudioThumb");
                if (thumb && audio.thumbnail_url) {
                    thumb.src = audio.thumbnail_url;
                    thumb.alt = audio.title || "Broadcast thumbnail";
                    thumb.classList.remove("is-hidden");
                } else if (thumb) {
                    thumb.classList.add("is-hidden");
                }
                mgr.load(audio);
                renderLiveAudioPlatforms(audio, mgr);
                var shareBtn = document.getElementById("liveAudioShare");
                if (shareBtn) {
                    shareBtn.onclick = function () {
                        var url = (audio.share_urls && audio.share_urls.whatsapp) || window.location.href.split("#")[0] + "#listen-live";
                        if (navigator.share) {
                            navigator.share({ title: audio.title, url: url }).catch(function () {});
                        } else if (url) {
                            window.open(url, "_blank", "noopener");
                        }
                    };
                }
            } else {
                mgr.stop();
            }
        } else if (audioSec) {
            audioSec.classList.add("is-hidden");
            mgr.stop();
        }

        if (!live && !audio && upcoming && upSec) {
            if (liveSec) liveSec.classList.add("is-hidden");
            if (audioSec) audioSec.classList.add("is-hidden");
            mgr.stop();
            upSec.classList.remove("is-hidden");
            document.getElementById("upcomingTitle").textContent = upcoming.title || "Upcoming Live Service";
            document.getElementById("upcomingDate").textContent = formatDate(upcoming.stream_date);
            document.getElementById("upcomingTime").textContent = (upcoming.start_time || "").substring(0, 5);
            document.getElementById("upcomingMinister").textContent = upcoming.minister_name || "";
            startCountdown(upcoming.starts_at);
            var form = document.getElementById("reminderForm");
            if (form) form.setAttribute("data-stream-id", String(upcoming.id));
        } else if (!live && !audio) {
            if (upSec && !upcoming) upSec.classList.add("is-hidden");
        }
    }

    function startCountdown(startsAt) {
        if (apiState.countdownTimer) clearInterval(apiState.countdownTimer);
        if (!startsAt) return;
        var target = new Date(startsAt.replace(" ", "T")).getTime();
        function tick() {
            var diff = Math.max(0, target - Date.now());
            var d = Math.floor(diff / 86400000);
            var h = Math.floor((diff % 86400000) / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            var elD = document.getElementById("cdDays");
            if (elD) {
                elD.textContent = d;
                document.getElementById("cdHours").textContent = h;
                document.getElementById("cdMinutes").textContent = m;
                document.getElementById("cdSeconds").textContent = s;
            }
        }
        tick();
        apiState.countdownTimer = setInterval(tick, 1000);
    }

    function startPublicSse() {
        if (typeof EventSource === "undefined") return;
        try {
            apiState.sse = new EventSource(API_BASE + "?action=stream");
            apiState.sse.addEventListener("live", function (ev) {
                try {
                    var data = JSON.parse(ev.data);
                    apiState.liveStream = data.live_stream;
                    apiState.liveAudio = data.live_audio;
                    renderLiveSections(data);
                    updateWatchLiveNav(!!data.show_watch_live_nav);
                    updateListenLiveNav(!!data.show_listen_live_nav);
                } catch (e) {}
            });
        } catch (e) {}
    }

    var PLAY_SVG = '<svg class="icon-play" width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
    var PAUSE_SVG = '<svg class="icon-pause" width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>';

    /* ========================================================================
       SermonAudioManager — single shared Audio instance
       ======================================================================== */
    function SermonAudioManager() {
        this.audio = new Audio();
        this.audio.preload = "metadata";
        this.currentId = null;
        this.isPlaying = false;
        this.isDragging = false;
        this.fallbackDuration = 0;

        this.playerEl = document.getElementById("audioPlayer");
        this.els = {
            thumb: document.getElementById("playerThumb"),
            title: document.getElementById("playerTitle"),
            speaker: document.getElementById("playerSpeaker"),
            playPause: document.getElementById("playerPlayPause"),
            current: document.getElementById("playerCurrent"),
            duration: document.getElementById("playerDuration"),
            fill: document.getElementById("progressFill"),
            thumbProgress: document.getElementById("progressThumb"),
            track: document.getElementById("progressTrack"),
            volume: document.getElementById("playerVolume"),
            speed: document.getElementById("playerSpeed"),
            close: document.getElementById("playerClose"),
            expand: document.getElementById("playerExpand")
        };

        this.bindAudioEvents();
        this.bindPlayerControls();
    }

    SermonAudioManager.prototype.formatTime = function (sec) {
        if (!isFinite(sec) || sec < 0) sec = 0;
        var h = Math.floor(sec / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = Math.floor(sec % 60);
        if (h > 0) {
            return h + ":" + String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
        }
        return m + ":" + String(s).padStart(2, "0");
    };

    SermonAudioManager.prototype.parseDuration = function (str) {
        var p = str.split(":").map(Number);
        if (p.length === 2) return p[0] * 60 + p[1];
        if (p.length === 3) return p[0] * 3600 + p[1] * 60 + p[2];
        return 0;
    };

    SermonAudioManager.prototype.getSermon = function (id) {
        return SERMONS.find(function (s) { return String(s.id) === String(id); });
    };

    SermonAudioManager.prototype.showPlayer = function () {
        this.playerEl.hidden = false;
        requestAnimationFrame(function () {
            this.playerEl.classList.add("is-visible");
            document.body.classList.add("has-audio-player");
        }.bind(this));
    };

    SermonAudioManager.prototype.hidePlayer = function () {
        this.playerEl.classList.remove("is-visible", "is-playing");
        document.body.classList.remove("has-audio-player", "player-expanded");
        setTimeout(function () {
            if (!this.playerEl.classList.contains("is-visible")) {
                this.playerEl.hidden = true;
            }
        }.bind(this), 500);
    };

    SermonAudioManager.prototype.syncCards = function () {
        var self = this;
        document.querySelectorAll(".sermon-card[data-id]").forEach(function (card) {
            var id = card.getAttribute("data-id");
            var active = String(id) === String(self.currentId);
            card.classList.toggle("is-active", active);
            card.classList.toggle("is-playing", active && self.isPlaying);
            var btn = card.querySelector(".card-play-btn");
            if (btn) {
                btn.setAttribute("aria-label", active && self.isPlaying
                    ? "Pause " + (self.getSermon(id) || {}).title
                    : "Play " + (self.getSermon(id) || {}).title);
            }
        });
    };

    SermonAudioManager.prototype.updatePlayerUI = function () {
        var sermon = this.getSermon(this.currentId);
        if (!sermon) return;

        this.els.thumb.className = "audio-player__thumb";
        if (sermon.image) {
            this.els.thumb.style.backgroundImage = "url('" + sermon.image.replace(/'/g, "%27") + "')";
        } else {
            this.els.thumb.style.backgroundImage = "";
            this.els.thumb.classList.add(sermon.thumb);
        }
        this.els.title.textContent = sermon.title;
        this.els.speaker.textContent = sermon.speaker;
        this.fallbackDuration = this.parseDuration(sermon.duration);

        this.playerEl.classList.toggle("is-playing", this.isPlaying);
        this.els.playPause.setAttribute("aria-label", this.isPlaying ? "Pause" : "Play");
        this.syncCards();
    };

    SermonAudioManager.prototype.updateProgress = function () {
        var dur = this.audio.duration;
        if (!isFinite(dur) || dur <= 0) dur = this.fallbackDuration;
        var cur = this.audio.currentTime || 0;
        var pct = dur > 0 ? (cur / dur) * 100 : 0;

        this.els.current.textContent = this.formatTime(cur);
        this.els.duration.textContent = this.formatTime(dur);
        this.els.fill.style.width = pct + "%";
        this.els.thumbProgress.style.left = pct + "%";
        this.els.track.setAttribute("aria-valuenow", Math.round(pct));
    };

    SermonAudioManager.prototype.play = function (sermon) {
        if (!sermon) return;

        if (String(this.currentId) !== String(sermon.id)) {
            this.currentId = sermon.id;
            this.audio.src = sermon.audioUrl;
            this.audio.playbackRate = parseFloat(this.els.speed.value) || 1;
        }

        this.showPlayer();
        this.updatePlayerUI();

        if (typeof USE_API !== "undefined" && USE_API && sermon.id) {
            fetch(API_BASE + "?action=track", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ sermon_id: sermon.id, event_type: "play" })
            }).catch(function () {});
        }

        var playPromise = this.audio.play();
        if (playPromise && playPromise.catch) {
            playPromise.catch(function () {
                this.isPlaying = false;
                this.updatePlayerUI();
            }.bind(this));
        }
    };

    SermonAudioManager.prototype.pause = function () {
        this.audio.pause();
    };

    SermonAudioManager.prototype.toggle = function (sermonId) {
        var sermon = this.getSermon(sermonId);
        if (!sermon) return;

        if (String(this.currentId) === String(sermonId) && this.isPlaying) {
            this.pause();
            return;
        }
        this.play(sermon);
    };

    SermonAudioManager.prototype.seekToRatio = function (ratio) {
        var dur = this.audio.duration;
        if (!isFinite(dur) || dur <= 0) dur = this.fallbackDuration;
        ratio = Math.max(0, Math.min(1, ratio));
        this.audio.currentTime = ratio * dur;
        this.updateProgress();
    };

    SermonAudioManager.prototype.stopAndClose = function () {
        this.pause();
        this.audio.src = "";
        this.currentId = null;
        this.isPlaying = false;
        this.hidePlayer();
        this.syncCards();
    };

    SermonAudioManager.prototype.bindAudioEvents = function () {
        var self = this;

        this.audio.addEventListener("play", function () {
            self.isPlaying = true;
            self.updatePlayerUI();
        });

        this.audio.addEventListener("pause", function () {
            self.isPlaying = false;
            self.updatePlayerUI();
        });

        this.audio.addEventListener("timeupdate", function () {
            if (!self.isDragging) self.updateProgress();
        });

        this.audio.addEventListener("loadedmetadata", function () {
            self.updateProgress();
        });

        this.audio.addEventListener("ended", function () {
            self.isPlaying = false;
            self.updatePlayerUI();
            self.updateProgress();
        });
    };

    SermonAudioManager.prototype.bindPlayerControls = function () {
        var self = this;

        this.els.playPause.addEventListener("click", function () {
            if (!self.currentId) return;
            if (self.isPlaying) self.pause();
            else self.audio.play();
        });

        this.els.close.addEventListener("click", function () {
            self.stopAndClose();
        });

        this.els.volume.addEventListener("input", function () {
            self.audio.volume = parseFloat(self.els.volume.value);
        });

        this.els.speed.addEventListener("change", function () {
            self.audio.playbackRate = parseFloat(self.els.speed.value);
        });

        this.els.expand.addEventListener("click", function () {
            var expanded = document.body.classList.toggle("player-expanded");
            self.playerEl.classList.toggle("player-expanded", expanded);
            self.els.expand.setAttribute("aria-expanded", String(expanded));
        });

        this.bindSeekBar();
    };

    SermonAudioManager.prototype.bindSeekBar = function () {
        var self = this;
        var track = this.els.track;

        function ratioFromEvent(e) {
            var rect = track.getBoundingClientRect();
            var x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
            return x / rect.width;
        }

        function startDrag(e) {
            self.isDragging = true;
            track.classList.add("is-dragging");
            self.seekToRatio(ratioFromEvent(e));
        }

        function moveDrag(e) {
            if (!self.isDragging) return;
            self.seekToRatio(ratioFromEvent(e));
        }

        function endDrag() {
            self.isDragging = false;
            track.classList.remove("is-dragging");
        }

        track.addEventListener("mousedown", startDrag);
        track.addEventListener("touchstart", startDrag, { passive: true });
        window.addEventListener("mousemove", moveDrag);
        window.addEventListener("touchmove", moveDrag, { passive: true });
        window.addEventListener("mouseup", endDrag);
        window.addEventListener("touchend", endDrag);

        track.addEventListener("keydown", function (e) {
            if (!self.currentId) return;
            var step = 0.05;
            if (e.key === "ArrowRight") {
                e.preventDefault();
                var r = (self.audio.currentTime || 0) / (self.audio.duration || self.fallbackDuration || 1);
                self.seekToRatio(r + step);
            }
            if (e.key === "ArrowLeft") {
                e.preventDefault();
                var r2 = (self.audio.currentTime || 0) / (self.audio.duration || self.fallbackDuration || 1);
                self.seekToRatio(r2 - step);
            }
        });
    };

    var audioManager = new SermonAudioManager();

    /* ========================================================================
       Page state & DOM
       ======================================================================== */
    var PER_PAGE = 8;
    var state = { query: "", category: "All", categorySlug: "", page: 1, apiTotal: 0, apiPages: 1, categorySlugs: {} };

    var filterChips = document.getElementById("filterChips");
    var sermonGrid = document.getElementById("sermonGrid");
    var featuredSermon = document.getElementById("featuredSermon");
    var trendingTrack = document.getElementById("trendingTrack");
    var seriesGrid = document.getElementById("seriesGrid");
    var speakersGrid = document.getElementById("speakersGrid");
    var pagination = document.getElementById("pagination");
    var searchInput = document.getElementById("sermonSearch");
    var searchClear = document.getElementById("searchClear");
    var searchMeta = document.getElementById("searchMeta");
    var emptyState = document.getElementById("emptyState");

    function formatDate(iso) {
        return new Date(iso).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
    }

    function debounce(fn, wait) {
        var t;
        return function () {
            var args = arguments;
            var ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function escapeHtml(str) {
        var div = document.createElement("div");
        div.textContent = str;
        return div.innerHTML;
    }

    function getFiltered() {
        if (USE_API && state.apiTotal) {
            return SERMONS;
        }
        var q = state.query.trim().toLowerCase();
        return SERMONS.filter(function (s) {
            var matchCat = state.category === "All" || s.category === state.category;
            if (!matchCat) return false;
            if (!q) return true;
            return (
                s.title.toLowerCase().indexOf(q) !== -1 ||
                s.speaker.toLowerCase().indexOf(q) !== -1 ||
                s.category.toLowerCase().indexOf(q) !== -1
            );
        });
    }

    function thumbPlayBlock(sermon) {
        var imgSrc = sermon.image ? escapeHtml(sermon.image) : "";
        var imgTag = imgSrc
            ? '  <img class="card-thumb-img" src="' + imgSrc + '" alt="" loading="lazy" decoding="async" onerror="this.classList.add(\'is-hidden\')">'
            : "";
        return (
            '<div class="card-thumb ' + sermon.thumb + '">' +
            '  <div class="card-thumb-inner ' + sermon.thumb + '" aria-hidden="true"></div>' +
            imgTag +
            '  <div class="card-play-zone">' +
            '    <span class="play-ring" aria-hidden="true"></span>' +
            '    <button type="button" class="card-play-btn" data-play-id="' + sermon.id + '" aria-label="Play ' + escapeHtml(sermon.title) + '">' +
            PLAY_SVG + PAUSE_SVG +
            "    </button>" +
            "  </div>" +
            '  <span class="now-playing-badge">Now Playing</span>' +
            '  <div class="eq-bars" aria-hidden="true"><span></span><span></span><span></span><span></span></div>' +
            '  <span class="duration-badge">' + escapeHtml(sermon.duration) + "</span>" +
            '  <span class="category-badge">' + escapeHtml(sermon.category) + "</span>" +
            "</div>"
        );
    }

    function cardHTML(sermon) {
        return (
            '<article class="sermon-card" data-id="' + sermon.id + '" tabindex="0">' +
            thumbPlayBlock(sermon) +
            '  <div class="card-body">' +
            "    <h3>" + escapeHtml(sermon.title) + "</h3>" +
            '    <p class="card-speaker">' + escapeHtml(sermon.speaker) + "</p>" +
            '    <div class="card-footer">' +
            "      <span>" + formatDate(sermon.date) + " · " + sermon.views.toLocaleString() + " views</span>" +
            "    </div>" +
            "  </div>" +
            "</article>"
        );
    }

    function renderFeatured() {
        var section = featuredSermon ? featuredSermon.closest(".section") : null;
        var s = window.__featuredOverride || SERMONS.find(function (x) { return x.featured; }) || null;
        if (!s) {
            if (section) section.classList.add("is-hidden");
            if (featuredSermon) featuredSermon.innerHTML = "";
            return;
        }
        if (section) section.classList.remove("is-hidden");
        featuredSermon.className = "featured-card sermon-card reveal is-visible";
        featuredSermon.setAttribute("data-id", s.id);
        featuredSermon.innerHTML =
            thumbPlayBlock(s) +
            '<div class="featured-body card-body">' +
            '  <span class="category">' + escapeHtml(s.category) + "</span>" +
            "  <h3>" + escapeHtml(s.title) + "</h3>" +
            '  <div class="featured-meta">' +
            "    <span>" + escapeHtml(s.speaker) + "</span>" +
            "    <span>" + formatDate(s.date) + "</span>" +
            "    <span>" + escapeHtml(s.duration) + "</span>" +
            "  </div>" +
            '  <p class="featured-desc">' + escapeHtml(s.desc || "") + "</p>" +
            "</div>";
    }

    function renderFilters() {
        filterChips.innerHTML = CATEGORIES.map(function (cat) {
            var active = cat === state.category ? " is-active" : "";
            return '<button type="button" class="filter-chip' + active + '" data-category="' + escapeHtml(cat) + '" role="tab" aria-selected="' + (cat === state.category) + '">' + escapeHtml(cat) + "</button>";
        }).join("");
    }

    function renderGrid() {
        var list = getFiltered();
        var totalPages = USE_API && state.apiPages ? state.apiPages : Math.max(1, Math.ceil(list.length / PER_PAGE));
        if (state.page > totalPages) state.page = totalPages;
        var pageItems = USE_API && state.apiTotal ? list : list.slice((state.page - 1) * PER_PAGE, state.page * PER_PAGE);
        var totalCount = USE_API && state.apiTotal ? state.apiTotal : list.length;
        sermonGrid.innerHTML = pageItems.map(cardHTML).join("");
        emptyState.classList.toggle("is-hidden", pageItems.length > 0);
        searchMeta.textContent = totalCount ? "Showing " + totalCount + " message" + (totalCount === 1 ? "" : "s") : "No messages found";
        renderPagination(totalPages);
        audioManager.syncCards();
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) { pagination.innerHTML = ""; return; }
        var html = '<button type="button" class="page-btn" data-page="prev"' + (state.page === 1 ? " disabled" : "") + ">Prev</button>";
        for (var i = 1; i <= totalPages; i++) {
            html += '<button type="button" class="page-btn' + (i === state.page ? " is-active" : "") + '" data-page="' + i + '">' + i + "</button>";
        }
        html += '<button type="button" class="page-btn" data-page="next"' + (state.page === totalPages ? " disabled" : "") + ">Next</button>";
        pagination.innerHTML = html;
    }

    function renderTrending() {
        var section = trendingTrack ? trendingTrack.closest(".section") : null;
        var trending = SERMONS.filter(function (s) { return s.trending; });
        if (!trending.length) {
            if (section) section.classList.add("is-hidden");
            trendingTrack.innerHTML = "";
            return;
        }
        if (section) section.classList.remove("is-hidden");
        trendingTrack.innerHTML = trending.map(cardHTML).join("");
        audioManager.syncCards();
    }

    function renderSeries() {
        var section = seriesGrid ? seriesGrid.closest(".section") : null;
        if (!SERIES.length) {
            if (section) section.classList.add("is-hidden");
            seriesGrid.innerHTML = "";
            return;
        }
        if (section) section.classList.remove("is-hidden");
        seriesGrid.innerHTML = SERIES.map(function (s) {
            var bg = s.image
                ? ' style="background-image:url(\'' + s.image.replace(/'/g, "%27") + "')"
                : "";
            return '<a href="#" class="series-card"><div class="series-card-bg ' + s.thumb + '"' + bg + '></div><div class="series-overlay"><h3>' + escapeHtml(s.title) + "</h3><p>" + s.count + " teachings · " + escapeHtml(s.speaker) + "</p></div></a>";
        }).join("");
    }

    function renderSpeakers() {
        var section = speakersGrid ? speakersGrid.closest(".section") : null;
        if (!SPEAKERS.length) {
            if (section) section.classList.add("is-hidden");
            speakersGrid.innerHTML = "";
            return;
        }
        if (section) section.classList.remove("is-hidden");
        speakersGrid.innerHTML = SPEAKERS.map(function (sp) {
            return '<div class="speaker-chip"><strong>' + escapeHtml(sp.name) + "</strong><span>" + escapeHtml(sp.role) + " · " + sp.count + " messages</span></div>";
        }).join("");
    }

    function bindPlayDelegation() {
        document.body.addEventListener("click", function (e) {
            var btn = e.target.closest(".card-play-btn");
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                audioManager.toggle(btn.getAttribute("data-play-id"));
                return;
            }
            var card = e.target.closest(".sermon-card[data-id]");
            if (card && !e.target.closest("a")) {
                var id = card.getAttribute("data-id");
                if (String(audioManager.currentId) === String(id) && audioManager.isPlaying) {
                    audioManager.pause();
                } else {
                    audioManager.toggle(id);
                }
            }
        });

        document.body.addEventListener("keydown", function (e) {
            if (e.key !== "Enter" && e.key !== " ") return;
            var card = e.target.closest(".sermon-card[data-id]");
            if (!card || e.target.closest(".card-play-btn")) return;
            e.preventDefault();
            audioManager.toggle(card.getAttribute("data-id"));
        });
    }

    function bindFilters() {
        filterChips.addEventListener("click", function (e) {
            var btn = e.target.closest(".filter-chip");
            if (!btn) return;
            state.category = btn.getAttribute("data-category");
            state.categorySlug = state.categorySlugs[state.category] || "";
            state.page = 1;
            renderFilters();
            loadFromApi().then(function () {
                renderSeries();
                renderSpeakers();
                renderFeatured();
                renderTrending();
                renderGrid();
            });
        });
    }

    function bindSearch() {
        var onSearch = debounce(function () {
            state.query = searchInput.value;
            state.page = 1;
            searchClear.classList.toggle("is-visible", state.query.length > 0);
            loadFromApi().then(function () { renderGrid(); });
        }, 280);
        searchInput.addEventListener("input", onSearch);
        searchClear.addEventListener("click", function () {
            searchInput.value = "";
            state.query = "";
            state.page = 1;
            searchClear.classList.remove("is-visible");
            searchInput.focus();
            loadFromApi().then(function () { renderGrid(); });
        });
    }

    function bindPagination() {
        pagination.addEventListener("click", function (e) {
            var btn = e.target.closest(".page-btn");
            if (!btn || btn.disabled) return;
            var p = btn.getAttribute("data-page");
            var totalPages = USE_API && state.apiPages ? state.apiPages : Math.max(1, Math.ceil(getFiltered().length / PER_PAGE));
            if (p === "prev") state.page = Math.max(1, state.page - 1);
            else if (p === "next") state.page = Math.min(totalPages, state.page + 1);
            else state.page = parseInt(p, 10);
            loadFromApi().then(function () {
                renderGrid();
                document.getElementById("sermons").scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });
    }

    function bindTrendingScroll() {
        var track = trendingTrack;
        document.getElementById("trendPrev").addEventListener("click", function () { track.scrollBy({ left: -320, behavior: "smooth" }); });
        document.getElementById("trendNext").addEventListener("click", function () { track.scrollBy({ left: 320, behavior: "smooth" }); });
    }

    function bindHeader() {
        /* Site chrome uses Bootstrap navbar from ../index.php pattern */
    }

    function bindSubscribe() {
        // Footer/site newsletter form is handled by js/newsletter.js
        if (document.getElementById("agNewsletterForm") || document.querySelector("form[data-newsletter]")) {
            // continue to reminder form binding below
        } else {
            var legacyForm = document.getElementById("subscribeForm");
            if (legacyForm) {
                legacyForm.addEventListener("submit", function (e) {
                    e.preventDefault();
                    var email = document.getElementById("subscribeEmail");
                    var msg = document.getElementById("subscribeMessage");
                    if (!email.validity.valid) { msg.textContent = "Please enter a valid email."; return; }
                    fetch(API_BASE + "?action=subscribe_newsletter", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ email: email.value })
                    }).then(function (r) { return r.json(); }).then(function (d) {
                        msg.textContent = (d && d.message) || "Thank you! You are subscribed.";
                        email.value = "";
                    }).catch(function () {
                        msg.textContent = "Thank you! You are subscribed.";
                        email.value = "";
                    });
                });
            }
        }

        var reminderForm = document.getElementById("reminderForm");
        if (reminderForm) {
            reminderForm.addEventListener("submit", function (e) {
                e.preventDefault();
                var msg = document.getElementById("reminderMessage");
                var fd = new FormData(reminderForm);
                var streamId = parseInt(reminderForm.getAttribute("data-stream-id") || "0", 10);
                fetch(API_BASE + "?action=subscribe_reminder", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        stream_id: streamId,
                        name: fd.get("name"),
                        email: fd.get("email"),
                        phone: fd.get("phone"),
                        notify_email: true,
                        notify_whatsapp: !!fd.get("notify_whatsapp")
                    })
                }).then(function (r) { return r.json(); }).then(function (d) {
                    msg.textContent = (d && d.message) || "Reminder saved.";
                    reminderForm.reset();
                }).catch(function () {
                    msg.textContent = "Could not save reminder. Please try again.";
                });
            });
        }
    }

    function initReveal() {
        if (!("IntersectionObserver" in window)) {
            document.querySelectorAll(".reveal").forEach(function (el) { el.classList.add("is-visible"); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
        document.querySelectorAll(".reveal").forEach(function (el, i) {
            el.style.transitionDelay = Math.min(i * 0.06, 0.36) + "s";
            observer.observe(el);
        });
    }

    function observeGridCards() {
        sermonGrid.querySelectorAll(".sermon-card").forEach(function (card, i) {
            card.style.opacity = "0";
            card.style.transform = "translateY(16px)";
            card.style.transition = "opacity 0.5s ease " + i * 0.05 + "s, transform 0.5s ease " + i * 0.05 + "s";
            requestAnimationFrame(function () {
                card.style.opacity = "1";
                card.style.transform = "";
            });
        });
    }

    var _renderGrid = renderGrid;
    renderGrid = function () {
        _renderGrid();
        observeGridCards();
    };

    function initSiteChrome() {
        var fixedTop = document.querySelector(".fixed-top");
        var topbar = document.querySelector(".topbar");
        if (!fixedTop) return;
        fixedTop.classList.add("bg-white", "shadow");
        if (topbar && topbar.offsetHeight) {
            fixedTop.style.top = "-" + topbar.offsetHeight + "px";
        }
    }

    function init() {
        initSiteChrome();
        loadFromApi().then(function () {
            renderFilters();
            renderFeatured();
            renderTrending();
            renderSeries();
            renderSpeakers();
            renderGrid();
            bindPlayDelegation();
            bindFilters();
            bindSearch();
            bindPagination();
            bindTrendingScroll();
            bindHeader();
            bindSubscribe();
            initReveal();
            audioManager.syncCards();
            startPublicSse();
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
