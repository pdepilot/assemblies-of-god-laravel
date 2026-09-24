(function () {
    'use strict';

    var MAX_BYTES = 2097152;
    var MAX_DIM = 1200;
    var JPEG_QUALITY = 0.88;
    var ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    var input = document.getElementById('photo');
    var preview = document.getElementById('memberPhotoPreview');
    var cameraBtn = document.getElementById('memberTakePhotoBtn');
    var statusEl = document.getElementById('memberPhotoStatus');
    if (!input || !cameraBtn) {
        return;
    }

    var modal = null;
    var stream = null;
    var facing = 'user';

    function setStatus(message, isError) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message || '';
        statusEl.classList.toggle('is-error', !!isError && !!message);
    }

    function showPreview(url) {
        if (!preview) {
            return;
        }
        if (!url) {
            preview.innerHTML = '<span class="member-photo-capture__placeholder">No photo</span>';
            return;
        }
        preview.innerHTML = '<img src="' + url + '" alt="Member photo preview">';
    }

    function assignFile(file) {
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        } catch (err) {
            setStatus('Camera capture is not supported in this browser. Please upload a file instead.', true);
            return false;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            showPreview(e.target.result);
        };
        reader.readAsDataURL(file);
        setStatus('Photo ready. It will be saved when you submit the form.', false);
        var remove = document.querySelector('input[name="remove_photo"]');
        if (remove) {
            remove.checked = false;
        }
        return true;
    }

    function canvasToFile(canvas, fileName, callback) {
        canvas.toBlob(function (blob) {
            if (!blob) {
                callback(null);
                return;
            }
            callback(new File([blob], fileName, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', JPEG_QUALITY);
    }

    function resizeFile(file, callback) {
        if (file.size <= MAX_BYTES) {
            callback(file);
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var w = img.width;
                var h = img.height;
                if (w > MAX_DIM || h > MAX_DIM) {
                    if (w >= h) {
                        h = Math.round((h * MAX_DIM) / w);
                        w = MAX_DIM;
                    } else {
                        w = Math.round((w * MAX_DIM) / h);
                        h = MAX_DIM;
                    }
                }
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvasToFile(canvas, file.name || 'member-photo.jpg', callback);
            };
            img.onerror = function () { callback(null); };
            img.src = e.target.result;
        };
        reader.onerror = function () { callback(null); };
        reader.readAsDataURL(file);
    }

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
    }

    function closeModal() {
        stopStream();
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    function startPreview() {
        var video = modal.querySelector('video');
        stopStream();
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Camera is not supported on this device. Please upload a photo instead.', true);
            closeModal();
            return;
        }
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 960 } },
            audio: false
        }).then(function (nextStream) {
            stream = nextStream;
            video.srcObject = nextStream;
        }).catch(function () {
            setStatus('Unable to access the camera. Check permissions or upload a file instead.', true);
            closeModal();
        });
    }

    function capture() {
        var video = modal.querySelector('video');
        var canvas = modal.querySelector('canvas');
        if (!video.videoWidth) {
            return;
        }
        var w = video.videoWidth;
        var h = video.videoHeight;
        if (w > MAX_DIM || h > MAX_DIM) {
            if (w >= h) {
                h = Math.round((h * MAX_DIM) / w);
                w = MAX_DIM;
            } else {
                w = Math.round((w * MAX_DIM) / h);
                h = MAX_DIM;
            }
        }
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(video, 0, 0, w, h);
        canvasToFile(canvas, 'camera-photo.jpg', function (file) {
            if (!file) {
                setStatus('Could not capture photo. Please try again.', true);
                return;
            }
            resizeFile(file, function (finalFile) {
                if (!finalFile || finalFile.size > MAX_BYTES) {
                    setStatus('Photo must be 2 MB or smaller.', true);
                    return;
                }
                if (assignFile(finalFile)) {
                    closeModal();
                }
            });
        });
    }

    function ensureModal() {
        if (modal) {
            return modal;
        }
        modal = document.createElement('div');
        modal.className = 'member-camera-modal';
        modal.id = 'memberCameraModal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML =
            '<div class="member-camera-modal__backdrop" data-close-camera></div>' +
            '<div class="member-camera-modal__dialog" role="dialog" aria-labelledby="memberCameraTitle">' +
            '  <div class="member-camera-modal__head">' +
            '    <h3 id="memberCameraTitle"><i class="fas fa-camera"></i> Take Photo</h3>' +
            '    <button type="button" class="member-camera-modal__close" data-close-camera aria-label="Close">&times;</button>' +
            '  </div>' +
            '  <div class="member-camera-modal__body">' +
            '    <video autoplay playsinline muted></video>' +
            '    <canvas hidden></canvas>' +
            '    <p class="member-camera-modal__hint">Position the face in the frame, then tap Capture.</p>' +
            '  </div>' +
            '  <div class="member-camera-modal__foot">' +
            '    <button type="button" class="member-photo-capture__camera-btn member-photo-capture__camera-btn--ghost" id="memberCameraSwitch"><i class="fas fa-sync-alt"></i> Switch</button>' +
            '    <button type="button" class="member-photo-capture__camera-btn member-photo-capture__camera-btn--ghost" data-close-camera>Cancel</button>' +
            '    <button type="button" class="member-photo-capture__camera-btn" id="memberCameraCapture"><i class="fas fa-camera"></i> Capture</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(modal);
        modal.querySelectorAll('[data-close-camera]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        modal.querySelector('#memberCameraSwitch').addEventListener('click', function () {
            facing = facing === 'user' ? 'environment' : 'user';
            startPreview();
        });
        modal.querySelector('#memberCameraCapture').addEventListener('click', capture);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
        return modal;
    }

    cameraBtn.addEventListener('click', function () {
        setStatus('', false);
        ensureModal();
        facing = 'user';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        startPreview();
    });

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) {
            return;
        }
        if (ALLOWED.indexOf(file.type) === -1) {
            setStatus('Photo must be JPG, PNG, or WebP.', true);
            input.value = '';
            return;
        }
        if (file.size > MAX_BYTES) {
            setStatus('Photo must be 2 MB or smaller.', true);
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            showPreview(e.target.result);
        };
        reader.readAsDataURL(file);
        setStatus('', false);
    });
})();
