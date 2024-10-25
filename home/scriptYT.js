
(function() {
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
})();

// Array per memorizzare i tre player
let players = [];

// Funzione per rimuovere tutti i player attivi
function clearPlayers() {
    players.forEach(player => {
        if (player && typeof player.destroy === "function") {
            player.destroy();  // Distrugge il player
        }
    });
    players = []; // Svuota l'array dei player
}

// Funzione chiamata quando l'API YouTube è pronta
function onYouTubeIframeAPIReady() {
    // Pulisce i player esistenti prima di creare i nuovi
    clearPlayers();

    players[1] = new YT.Player('player1', {
        height: '390',
        width: '640',
        videoId: 'vHJ2C976Rt4', // Sostituisci con l'ID del primo video
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });

    players[2] = new YT.Player('player2', {
        height: '390',
        width: '640',
        videoId: 'EnFHmRxj4hA', // Sostituisci con l'ID del secondo video
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });

    players[3] = new YT.Player('player3', {
        height: '390',
        width: '640',
        videoId: 'ATnETqrWbwo', // Sostituisci con l'ID del terzo video
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

// Funzione di callback quando il player è pronto
function onPlayerReady(event) {
    console.log("Il video è pronto per essere riprodotto");
}

// Funzione di callback per gestire il cambio di stato
function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED) {
        console.log("Il video è terminato");
    }
}

// Funzioni per controllare i video in base all'indice
function playVideo(index) {
    players[index].playVideo();
}

function pauseVideo(index) {
    players[index].pauseVideo();
}