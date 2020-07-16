const userID = document.querySelector('main').getAttribute('data-id');


fetch(`/api/users/${userID}/stats`)
.then(function (response) {
    return response.json();
})
.then(function (tagStats) {
    buildStatsPieChart(tagStats.tags_on_posts);
    buildLocationStats(tagStats.subs_location);
    buildSubsAgeStats(tagStats.subs_age);
})
.catch(function (error) {
    addFloatingAlert("An unknown error occurred. Please reload the page.");
})

function buildLocationStats(subsLocation) {

    labels = [];
    data = [];
    backgroundColor = [];

    for (let i = 0; i < subsLocation.length; i++) {
        if (i < 5) {

            labels.push(capitalize(subsLocation[i].name));
            data.push(subsLocation[i].frequency);
            backgroundColor.push(defaultColor[i % 2]);
        } else if (i == 5) {
            labels[5] = 'Other';
            data[5] = subsLocation[i].frequency;
            backgroundColor[5] = defaultColor[5];
        } else {
            data[5] += subsLocation[i].frequency;
        }
    }

    let locCtx = document.getElementById('locStats').getContext('2d');

    let myLocChart = new Chart(locCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColor,
                hoverBackgroundColor: backgroundColor,
                borderWidth: 0,
            }]
        },
        options: {
            legend: {
                display: false
            }
        }
    });
}

function buildSubsAgeStats(subsAge) {

    let ageCtx = document.getElementById('ageStats').getContext('2d');
    let myAgeChart = new Chart(ageCtx, {
        type: 'line',
        data: {

            labels: Object.keys(subsAge),
            datasets: [{
                label: 'Number of Readers',
                borderColor: defaultColor[0],
                data: Object.values(subsAge),
                fill: true,
            }]
        },
        options: {
            legend: {
                display: false
            }
        }
    });
}


