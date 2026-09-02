/**
 * Website Blog Studio — cover dropzone preview (jenfrank portal/blog pattern).
 */
(function () {
    "use strict";

    function initCoverDropzone() {
        var dropzone = document.getElementById("blogCoverDropzone");
        var input = document.getElementById("coverFile");
        var preview = document.getElementById("coverPreview");
        var clearBtn = document.getElementById("coverClear");
        if (!dropzone || !input || !preview) return;

        var existingSrc = preview.getAttribute("src") || "";

        function showPreview(file) {
            if (!file || !file.type || file.type.indexOf("image/") !== 0) return;
            if (file.size > 5 * 1024 * 1024) {
                window.alert("Cover image must be 5 MB or smaller.");
                input.value = "";
                return;
            }

            var reader = new FileReader();
            reader.onload = function () {
                preview.src = String(reader.result || "");
                preview.classList.add("show");
            };
            reader.readAsDataURL(file);
        }

        input.addEventListener("change", function () {
            showPreview(input.files && input.files[0] ? input.files[0] : null);
        });

        ["dragenter", "dragover"].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.add("drag");
            });
        });

        ["dragleave", "drop"].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.remove("drag");
            });
        });

        dropzone.addEventListener("drop", function (event) {
            var file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
            if (!file) return;
            try {
                var transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
            } catch (err) {
                /* browsers without DataTransfer still allow click-to-upload */
            }
            showPreview(file);
        });

        if (clearBtn) {
            clearBtn.addEventListener("click", function () {
                input.value = "";
                if (existingSrc) {
                    preview.src = existingSrc;
                    preview.classList.add("show");
                } else {
                    preview.removeAttribute("src");
                    preview.classList.remove("show");
                }
            });
        }
    }

    document.addEventListener("DOMContentLoaded", initCoverDropzone);
})();
