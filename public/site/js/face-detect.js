/**
 * Lightweight face detection for member photos.
 * Uses Chromium FaceDetector when available, otherwise BlazeFace via CDN.
 */
(function (global) {
    'use strict';

    var blazeModel = null;
    var blazeLoading = null;
    var FACE_MSG = 'Please use a clear facial photo of yourself (one face, facing the camera).';

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (document.querySelector('script[src="' + src + '"]')) {
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('Failed to load face detection library.')); };
            document.head.appendChild(s);
        });
    }

    function fileToImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                resolve(img);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Could not read that image.'));
            };
            img.src = url;
        });
    }

    function canvasFromImage(img) {
        var max = 640;
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        if (w > max || h > max) {
            if (w >= h) {
                h = Math.round((h * max) / w);
                w = max;
            } else {
                w = Math.round((w * max) / h);
                h = max;
            }
        }
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        return canvas;
    }

    function detectWithNative(imgOrCanvas) {
        if (typeof FaceDetector === 'undefined') {
            return Promise.resolve(null);
        }
        try {
            var detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 5 });
            return detector.detect(imgOrCanvas).then(function (faces) {
                return Array.isArray(faces) ? faces.length : 0;
            }).catch(function () {
                return null;
            });
        } catch (e) {
            return Promise.resolve(null);
        }
    }

    function ensureBlazeFace() {
        if (blazeModel) return Promise.resolve(blazeModel);
        if (blazeLoading) return blazeLoading;

        blazeLoading = loadScript('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js')
            .then(function () {
                return loadScript('https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js');
            })
            .then(function () {
                if (typeof blazeface === 'undefined') {
                    throw new Error('Face detection is unavailable in this browser.');
                }
                return blazeface.load();
            })
            .then(function (model) {
                blazeModel = model;
                blazeLoading = null;
                return model;
            })
            .catch(function (err) {
                blazeLoading = null;
                throw err;
            });

        return blazeLoading;
    }

    function detectWithBlaze(canvas) {
        return ensureBlazeFace().then(function (model) {
            return model.estimateFaces(canvas, false);
        }).then(function (preds) {
            return Array.isArray(preds) ? preds.length : 0;
        });
    }

    /**
     * @param {File|Blob|HTMLImageElement|HTMLCanvasElement} source
     * @returns {Promise<{ok:boolean,faceCount:number,message?:string}>}
     */
    function validateFacialPhoto(source) {
        var ready = Promise.resolve(source);

        if (source instanceof Blob || (typeof File !== 'undefined' && source instanceof File)) {
            ready = fileToImage(source).then(function (img) {
                return canvasFromImage(img);
            });
        } else if (source && (source.tagName === 'IMG' || source.tagName === 'VIDEO')) {
            ready = Promise.resolve(canvasFromImage(source));
        }

        return ready.then(function (canvas) {
            return detectWithNative(canvas).then(function (nativeCount) {
                if (nativeCount !== null) {
                    return { faceCount: nativeCount, canvas: canvas };
                }
                return detectWithBlaze(canvas).then(function (count) {
                    return { faceCount: count, canvas: canvas };
                });
            });
        }).then(function (result) {
            var count = result.faceCount;
            if (count < 1) {
                return { ok: false, faceCount: count, message: FACE_MSG };
            }
            if (count > 1) {
                return {
                    ok: false,
                    faceCount: count,
                    message: 'Multiple faces detected. This photo was not accepted; keep only your face in the frame.'
                };
            }
            return { ok: true, faceCount: 1 };
        }).catch(function (err) {
            return {
                ok: false,
                faceCount: 0,
                message: (err && err.message) || 'Unable to verify a face in this photo. Please try again.'
            };
        });
    }

    global.AGFaceDetect = {
        validateFacialPhoto: validateFacialPhoto,
        FACE_MSG: FACE_MSG
    };
})(window);
