/* 
STATUS:WIP

*/

var data = [];
const xhr = new XMLHttpRequest();
console.log('XMLHttpRequest created');

xhr.open('GET', 'dota2_db.php', true);
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
    computePlayerStatistics(data, player_id); // Test for a specific player
    const resultsDiv = document.getElementById('results');
    resultsDiv.insertAdjacentHTML('beforeend', `<pre>${JSON.stringify(computePlayerStatistics(data, playerId), null, 2)}</pre>`);
};

xhr.onerror = function () {
    console.error('Request error');
};

console.log('Sending request');
xhr.send();

function computeMatchStatistics(matches) {
    console.log('Computing match statistics for', matches.length, 'matches');

    var totalDamageDealt = 0;
    var totalKills = 0;
    var totalDeaths = 0;
    var totalAssists = 0;
    var totalGoldEarned = 0;
    var totalWins = 0;
    var totalGames = matches.length;

    matches.forEach((match) => {
        match.players.forEach((player) => {
            totalDamageDealt += player.damage_dealt || 0;
            totalKills += player.kills || 0;
            totalDeaths += player.deaths || 0;
            totalAssists += player.assists || 0;
            totalGoldEarned += player.gold_earned || 0;
            totalWins += player.win ? 1 : 0;
        });
    });

    //DEBUG LOG
    console.log('Total damage dealt:', totalDamageDealt);
    console.log('Total kills:', totalKills);
    console.log('Total deaths:', totalDeaths);
    console.log('Total assists:', totalAssists);
    console.log('Total gold earned:', totalGoldEarned);
    console.log('Total wins:', totalWins);

    const averageDamageDealt = totalDamageDealt / totalGames;
    const averageKills = totalKills / totalGames;
    const averageDeaths = totalDeaths / totalGames;
    const averageAssists = totalAssists / totalGames;
    const averageGoldEarned = totalGoldEarned / totalGames;
    const winRate = (totalWins / totalGames) * 100;

    //DEBUG LOG
    console.log('Average damage dealt:', averageDamageDealt);
    console.log('Average kills:', averageKills);
    console.log('Average deaths:', averageDeaths);
    console.log('Average assists:', averageAssists);
    console.log('Average gold earned:', averageGoldEarned);
    console.log('Win rate:', winRate);

    return {
        averageDamageDealt,
        averageKills,
        averageDeaths,
        averageAssists,
        averageGoldEarned,
        winRate,
        totalGames,
    };
}

/*
Computes statistics for a specific player across multiple matches
- param {Array} matches - Array of match objects
- param {string} playerId - ID of the player to compute statistics for
- returns {Object} - Statistics object with the following properties:
- averageDamageDealt: average damage dealt per match
- averageKills: average kills per match
- averageDeaths: average deaths per match
- averageAssists: average assists per match
- averageGoldEarned: average gold earned per match
- winRate: win rate as a percentage
- totalGames: total number of games played
 */
function computePlayerStatistics(matches, account_id) {
    console.log('Computing player statistics for player', account_id, 'across', matches.length, 'matches');
    const playerMatches = matches.filter(match => match.players && Array.isArray(match.players) && match.players.some(player => player.account_id === account_id));
    console.log('Found', playerMatches.length, 'matches for player', account_id);
    const totalDamageDealt = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + player.damage_dealt;
    }, 0);
    console.log('Total damage dealt:', totalDamageDealt);
    const totalKills = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + player.kills;
    }, 0);
    console.log('Total kills:', totalKills);
    const totalDeaths = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + player.deaths;
    }, 0);
    console.log('Total deaths:', totalDeaths);
    const totalAssists = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + player.assists;
    }, 0);
    console.log('Total assists:', totalAssists);
    const totalGoldEarned = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + player.gold_earned;
    }, 0);
    console.log('Total gold earned:', totalGoldEarned);
    const totalWins = playerMatches.reduce((acc, match) => {
        const player = match.players.find(player => player.account_id === account_id);
        return acc + (player.win ? 1 : 0);
    }, 0);
    console.log('Total wins:', totalWins);
    const totalGames = playerMatches.length;
    console.log('Total games:', totalGames);

    if (totalGames === 0) {
        return {
            message: 'Player not found in any matches',
        };
    }

    const averageDamageDealt = totalDamageDealt / totalGames;
    console.log('Average damage dealt:', averageDamageDealt);
    const averageKills = totalKills / totalGames;
    console.log('Average kills:', averageKills);
    const averageDeaths = totalDeaths / totalGames;
    console.log('Average deaths:', averageDeaths);
    const averageAssists = totalAssists / totalGames;
    console.log('Average assists:', averageAssists);
    const averageGoldEarned = totalGoldEarned / totalGames;
    console.log('Average gold earned:', averageGoldEarned);
    const winRate = (totalWins / totalGames) * 100;
    console.log('Win rate:', winRate);

    return {
        averageDamageDealt,
        averageKills,
        averageDeaths,
        averageAssists,
        averageGoldEarned,
        winRate,
        totalGames,
    };
}


