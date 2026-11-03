<script>
// Shrink big phone photos in the browser before uploading (falls back to the originals).
window.arShrinkFiles = function (input, onDone) {
    var files = Array.prototype.slice.call(input.files || []);
    if (!files.length || !window.DataTransfer) { if (onDone) onDone(files); return; }
    var out = new Array(files.length), left = files.length;
    function finish() {
        if (--left > 0) return;
        try { var dt = new DataTransfer(); out.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; } catch (e) {}
        if (onDone) onDone(Array.prototype.slice.call(input.files));
    }
    files.forEach(function (file, i) {
        out[i] = file;
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { finish(); return; }
        var img = new Image(), url = URL.createObjectURL(file);
        img.onload = function () {
            var max = 1600, scale = Math.min(1, max / Math.max(img.width, img.height));
            if (scale === 1 && file.size < 1500000) { URL.revokeObjectURL(url); finish(); return; }
            var c = document.createElement('canvas');
            c.width = Math.round(img.width * scale); c.height = Math.round(img.height * scale);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            c.toBlob(function (blob) {
                URL.revokeObjectURL(url);
                if (blob) out[i] = new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
                finish();
            }, 'image/jpeg', 0.85);
        };
        img.onerror = function () { URL.revokeObjectURL(url); finish(); };
        img.src = url;
    });
};
</script>