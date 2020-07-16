const userID = document.querySelector('main').getAttribute('data-id')
if (!validPositiveInt(userID))
    addFloatingAlert("Invalid user ID. Please consider refreshing the page...")

fetch(`/api/users/${userID}/tags`)
    .then(function (response) {
        return response.json();
    })
    .then(function (tagStats) {
        buildStatsPieChart(tagStats);
    })
    .catch(function (error) {
        addFloatingAlert("An unknown error occurred. Please reload the page.");
    })