/*
Computes statistics across multiple matches
- matches - Array of match objects
- returns - Statistics object with the following properties:
- averageDamageDealt: average damage dealt per match
- averageKills: average kills per match
- averageDeaths: average deaths per match
- averageAssists: average assists per match
- averageGoldEarned: average gold earned per match
- winRate: win rate as a percentage
- totalGames: total number of games played
 */

//DEBUG match
const match = {
};
const match2 = {
  }; 
const matches = [
    match,
    match2
] 

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
        match.participants.forEach((participant) => {
            totalDamageDealt += participant.totalDamageDealt || 0;
            totalKills += participant.kills || 0;
            totalDeaths += participant.deaths || 0;
            totalAssists += participant.assists || 0;
            totalGoldEarned += participant.goldEarned || 0;
            totalWins += participant.win ? 1 : 0;
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
function computePlayerStatistics(matches, playerId) {
    console.log('Computing player statistics for player', playerId, 'across', matches.length, 'matches');

    var totalDamageDealt = 0;
    var totalKills = 0;
    var totalDeaths = 0;
    var totalAssists = 0;
    var totalGoldEarned = 0;
    var totalWins = 0;
    var totalGames = 0;

    matches.forEach((match) => {
        const participant = match.participants.find(p => p.puuid === playerId || p.summonerId === playerId);

        if (participant) {
            totalDamageDealt += participant.totalDamageDealt || 0;
            totalKills += participant.kills || 0;
            totalDeaths += participant.deaths || 0;
            totalAssists += participant.assists || 0;
            totalGoldEarned += participant.goldEarned || 0;
            totalWins += participant.win ? 1 : 0;
            totalGames++;
        }
    });

    //DEBUG LOG
    console.log('Total damage dealt:', totalDamageDealt);
    console.log('Total kills:', totalKills);
    console.log('Total deaths:', totalDeaths);
    console.log('Total assists:', totalAssists);
    console.log('Total gold earned:', totalGoldEarned);
    console.log('Total wins:', totalWins);

    if (totalGames === 0) {
        return {
            message: 'Player not found in any matches',
        };
    }

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

// Test with the example match data
const playerId = ''; // Replace with actual RIOT puid
computeMatchStatistics([match]); // Test with one match
computePlayerStatistics(matches, playerId); // Test for a specific player





