let darkmode = localStorage.getItem('darkmode')
var themeSwitch = document.getElementById('ui-switch')

var enableDarkMode = () => {
    document.body.classList.add('darkmode')
    localStorage.setItem('darkmode', 'active')
}

var disableDarkMode = () => {
    document.body.classList.remove('darkmode')
    localStorage.setItem('darkmode', null)
}

if (darkmode === 'active') {
    enableDarkMode()
}

themeSwitch.addEventListener('click', () => {
    darkmode = localStorage.getItem('darkmode')
    darkmode !== "active" ? enableDarkMode() : disableDarkMode()
})

// JS for Youtube Player API

// 1. This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');

tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// 2. This function creates an <iframe> (and YouTube player)
//    after the API code downloads.
var player;
function onYouTubeIframeAPIReady() {
    player1 = new YT.Player('player1', {
        height: '360',
        width: '640',
        videoId: 'kjBo6jNKfPw',
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });

    player2 = new YT.Player('player2', {
        height: '360',
        width: '640',
        videoId: '34P2acg-Wdo',
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

// 3. The API will call this function when the video player is ready.
function onPlayerReady(event) {
    event.target.playVideo();
}

// 4. The API calls this function when the player's state changes.
//    The function indicates that when playing a video (state=1),
//    the player should play for six seconds and then stop.
var done = false;
function onPlayerStateChange(event) {
    event.data == YT.PlayerState.PLAYING //it seems to work, and it doesn't stop :)
    //if (event.data == YT.PlayerState.PLAYING && !done) {
        //setTimeout(stopVideo, 10000);
        //done = true;
    //}
}
function stopVideo() {
    player.stopVideo();
} 