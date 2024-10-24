/* 
STATUS:WIP
TODO:
    - ADD radiant_score and dire_score to make more statistics
*/

var data = [];
const xhr = new XMLHttpRequest();
console.log('XMLHttpRequest created');

player_id = '107828036'; // GET ID OF THE PLAYER FROM SESSION
xhr.open('GET', 'dota2_db.php?player_id='+ player_id, true);
console.log('Request opened: GET dota2_db.php');

xhr.onload = function () {
    console.log('Request loaded');
    if (xhr.status === 200) {
        console.log('Request successful');
        console.log(xhr.responseText);
        data = JSON.parse(xhr.responseText);
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
    resultsDiv.insertAdjacentHTML('beforeend', `<pre>${JSON.stringify(computePlayerStatistics(data), null, 2)}</pre>`);
};

xhr.onerror = function () {
    console.error('Request error');
};

console.log('Sending request');
xhr.send();

/*
Computes statistics for a specific player across multiple matches
 */
function computePlayerStatistics(playerData) {
    console.log('Computing player statistics for player', playerData[0].personaname, 'across', playerData.length, 'matches');

    const totalGames = playerData.length;
    console.log('Total games:', totalGames);

    if (totalGames === 0) {
        return {
            message: 'Player not found in any matches',
        };
    }

    let totalDamageDealt = 0;
    let totalKills = 0;
    let totalDeaths = 0;
    let totalAssists = 0;
    let totalWins = 0;
    let totalLosses = 0;

    playerData.forEach(match => {
        totalKills += match.kills;
        totalDeaths += match.deaths;
        totalAssists += match.assists;
        
        totalWins = match.win;
        totalLosses = match.lose;
    });
    console.log('Total kills:', totalKills);
    console.log('Total deaths:', totalDeaths);
    console.log('Total assists:', totalAssists);
    console.log('Total wins:', totalWins);

    const averageDamageDealt = totalDamageDealt / totalGames;
    console.log('Average damage dealt:', averageDamageDealt);
    const averageKills = totalKills / totalGames;
    console.log('Average kills:', averageKills);
    const averageDeaths = totalDeaths / totalGames;
    console.log('Average deaths:', averageDeaths);
    const averageAssists = totalAssists / totalGames;
    console.log('Average assists:', averageAssists);
    const winRate = (totalWins / totalGames) * 100;
    console.log('Win rate:', winRate);

    return {
        averageDamageDealt,
        averageKills,
        averageDeaths,
        averageAssists,
        winRate,
        totalGames,
    };
}

