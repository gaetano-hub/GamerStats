/* 
STATUS:WIP
TODO:
    - ADD radiant_score and dire_score to make more statistics
*/

var data = [];
const xhr = new XMLHttpRequest();
console.log('XMLHttpRequest created');
var totalWinsD = 0;
var totalLossesD = 0;
var totalKillsD = 0;
var totalDeathsD = 0;
var totalAssistsD = 0;

player_id = '107828036'; // GET ID OF THE PLAYER FROM SESSION

xhr.open('GET', '../dota2_api/opendota_data_api.php?player_id=' + player_id, true);
console.log('Request opened: GET opendota_data_api.php');
xhr.send();
xhr.onload = function () {
    console.log('Request loaded');
    if (xhr.status === 200 && xhr.responseText !== '') {
        console.log('Request successful');
        console.log(xhr.responseText);
        data = JSON.parse(xhr.responseText);
        console.log('Response data:', data);
        console.log('matches:', data);
        computePlayerStatistics(data); // Test for a specific player
        const resultsDiv = document.getElementById('results');
        console.log('totalWinsD:', totalWinsD);
        console.log('totalLossesD:', totalLossesD);
        getChart();
        getChart2()
        document.getElementById('totalKillsDota2').innerHTML = totalKillsD;
        document.getElementById('totalDeathsDota2').innerHTML = totalDeathsD;
        document.getElementById('totalWinsDota2').innerHTML = totalWinsD;
    } else {
        console.error('Request failed with status:', xhr.status);
        if (xhr.responseText == '') { console.error('The OpenDotaAPI response is empty'); }
        xhr.onerror = function () {
            console.error('Request failed due to a network error');
        };

        const xhr2 = new XMLHttpRequest();
        console.log('XMLHttpRequest created');
        xhr2.open('GET', '../dota2/dota2_db.php?player_id=' + player_id, true);
        console.log('Request opened: GET dota2_db.php');
        console.log('Sending request');
        xhr2.send();
        xhr2.onload = function () {
            console.log('Request loaded');
            if (xhr2.status === 200) {
                console.log('Request successful');
                console.log(xhr2.responseText);
                data = JSON.parse(xhr2.responseText);
                console.log('Response data:', data);
            } else {
                console.error('Request failed with status:', data);
            }
            /*
            DEBUG
            */
            //computeMatchStatistics(data);
            console.log('matches:', data);
            computePlayerStatistics(data); // Test for a specific player
            const resultsDiv = document.getElementById('results');
            console.log('totalWinsD:', totalWinsD);
            console.log('totalLossesD:', totalLossesD);
            getChart();
            getChart2()
            document.getElementById('totalKillsDota2').innerHTML = totalKillsD;
            document.getElementById('totalDeathsDota2').innerHTML = totalDeathsD;
            document.getElementById('totalWinsDota2').innerHTML = totalWinsD;
            //resultsDiv.insertAdjacentHTML('beforeend', `<pre>${JSON.stringify(computePlayerStatistics(data), null, 2)}</pre>`);
        };

        xhr2.onerror = function () {
            console.error('Request error');
        };

        
    }
};




/*
Computes statistics for a specific player across multiple matches
 */

function getChart() {
    const winRatioChartD = new Chart(document.getElementById('winRatioChartD').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Wins', 'Losses'],
            datasets: [{
                data: [totalWinsD, totalLossesD],
                backgroundColor: ['#4caf50', '#f44336'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const value = tooltipItem.raw;
                            const total = totalWinsD + totalLossesD;
                            const percentage = total ? ((value / total) * 100).toFixed(2) : 0;
                            console.log('tooltipItem:', tooltipItem);
                            console.log('percentage:', percentage);
                            return tooltipItem.label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}
function getChart2() {
    const kdaChart = new Chart(document.getElementById('kdaChartD').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Kills', 'Deaths', 'Assists'],
            datasets: [{
                data: [totalKillsD, totalDeathsD, totalAssistsD],
                backgroundColor: ['#4caf50', '#f44336', '#ff9800'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const value = tooltipItem.raw;
                            return tooltipItem.label + ': ' + value;
                        }
                    }
                }
            },
        }
    });
}
function computePlayerStatistics(playerData) {
    console.log('Computing player statistics for player', playerData[0].personaname, 'across', playerData.length, 'matches');

    const totalGames = playerData.length;
    console.log('Total games:', totalGames);

    if (totalGames === 0) {
        return {
            message: 'Player not found in any matches',
        };
    }

    playerData.forEach(match => {
        totalKillsD += match.kills;
        totalDeathsD += match.deaths;
        totalAssistsD += match.assists;

        totalWinsD = match.win;
        totalLossesD = match.lose;
    });
    console.log('Total kills:', totalKillsD);
    console.log('Total deaths:', totalDeathsD);
    console.log('Total assists:', totalAssistsD);
    console.log('Total wins:', totalWinsD);

    const averageKills = totalKillsD / totalGames;
    console.log('Average kills:', averageKills);
    const averageDeaths = totalDeathsD / totalGames;
    console.log('Average deaths:', averageDeaths);
    const averageAssists = totalAssistsD / totalGames;
    console.log('Average assists:', averageAssists);
    const winRate = (totalWinsD / totalGames) * 100;
    console.log('Win rate:', winRate);

    return {
        averageKills,
        averageDeaths,
        averageAssists,
        winRate,
        totalWinsD,
        totalLossesD,
        totalGames,
    };
}

