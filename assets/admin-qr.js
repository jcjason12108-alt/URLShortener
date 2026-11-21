(function () {
    'use strict';

    function drawQr(canvas, url, size) {
        if (typeof qrcode !== 'function' || !url) {
            throw new Error('QR generator unavailable');
        }

        var qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();

        var count = qr.getModuleCount();
        var quietZone = 4;
        var cellSize = size / (count + quietZone * 2);
        var context = canvas.getContext('2d');
        canvas.width = size;
        canvas.height = size;
        context.fillStyle = '#fff';
        context.fillRect(0, 0, size, size);
        context.fillStyle = '#000';

        for (var row = 0; row < count; row++) {
            for (var column = 0; column < count; column++) {
                if (!qr.isDark(row, column)) {
                    continue;
                }

                var left = Math.round((column + quietZone) * cellSize);
                var top = Math.round((row + quietZone) * cellSize);
                var right = Math.round((column + quietZone + 1) * cellSize);
                var bottom = Math.round((row + quietZone + 1) * cellSize);
                context.fillRect(left, top, right - left, bottom - top);
            }
        }
    }

    function initialize() {
        document.querySelectorAll('.ius-qr-preview').forEach(function (canvas) {
            try {
                drawQr(canvas, canvas.dataset.iusQrUrl, Number(canvas.width));
            } catch (error) {
                canvas.setAttribute('aria-label', 'QR preview unavailable');
                canvas.title = 'QR preview unavailable';
            }
        });

        document.querySelectorAll('.ius-qr-download').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                try {
                    var canvas = document.createElement('canvas');
                    drawQr(canvas, button.dataset.iusQrUrl, 800);

                    var download = document.createElement('a');
                    download.href = canvas.toDataURL('image/png');
                    download.download = button.dataset.iusQrFilename || 'qr-code.png';
                    download.click();
                } catch (error) {
                    button.textContent = 'QR download unavailable';
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
