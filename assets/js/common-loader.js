// common-loader.js

// Agregar estilos del loader solo si no existen
(function() {
    if (!document.getElementById('loader-styles')) {
        const loaderStyles = `
            #loader-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 99999;
                justify-content: center;
                align-items: center;
                pointer-events: none;
            }

            .spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;

        const styleSheet = document.createElement("style");
        styleSheet.id = 'loader-styles';
        styleSheet.innerText = loaderStyles;
        document.head.appendChild(styleSheet);
    }
})();

/**
 * Muestra el overlay del loader.
 */
function showLoader() {
    const loaderOverlay = document.getElementById('loader-overlay');
    if (loaderOverlay) {
        loaderOverlay.style.display = 'flex';
        console.log('Loader mostrado'); // Para debugging
    } else {
        console.error('Elemento loader-overlay no encontrado');
    }
}

/**
 * Oculta el overlay del loader.
 */
function hideLoader() {
    const loaderOverlay = document.getElementById('loader-overlay');
    if (loaderOverlay) {
        loaderOverlay.style.display = 'none';
        console.log('Loader ocultado'); // Para debugging
    } else {
        console.error('Elemento loader-overlay no encontrado');
    }
}
