// common-loader.js

/**
 * Muestra el overlay del loader.
 */
function showLoader() {
    const loaderOverlay = document.getElementById('loader-overlay');
    if (loaderOverlay) {
        loaderOverlay.style.display = 'flex';
    }
}

/**
 * Oculta el overlay del loader.
 */
function hideLoader() {
    const loaderOverlay = document.getElementById('loader-overlay');
    if (loaderOverlay) {
        loaderOverlay.style.display = 'none';
    }
}
