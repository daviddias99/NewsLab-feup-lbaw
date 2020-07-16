let primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary')
let secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary')
let infoColor = getComputedStyle(document.documentElement).getPropertyValue('--info')
let successColor = getComputedStyle(document.documentElement).getPropertyValue('--success')
let dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--danger')
let warningColor = getComputedStyle(document.documentElement).getPropertyValue('--warning')

let defaultColor = [convertHex(primaryColor, 100),
convertHex(secondaryColor, 100),
convertHex(infoColor, 100),
convertHex(successColor, 100),
convertHex(dangerColor, 100),
convertHex(warningColor, 100)]

let hoverColor = [convertHex(primaryColor, 80),
convertHex(secondaryColor, 80),
convertHex(infoColor, 80),
convertHex(successColor, 80),
convertHex(dangerColor, 80),
convertHex(warningColor, 80)]

function capitalize(word) {
    return word.replace(/^./, word[0].toUpperCase())
}

function buildStatsPieChart(tagStats) {
    const canvas = document.getElementById('tagsStats');

    if (tagStats.length == 0) {
        const parentNode = document.getElementById('tagsOnPosts');
        parentNode.removeChild(canvas);
        const text = document.createElement('p');
        text.setAttribute('class', 'font-italic text-muted');
        text.textContent = 'This user hasn\'t posted anything yet';
        parentNode.appendChild(text);
        return;
    }

    let labels = [];
    let data = [];

    for (let i = 0; i < tagStats.length; i++) {
        if (i < 5) {
            labels.push(capitalize(tagStats[i].name));
            data.push(tagStats[i].frequency);
        } else if (i == 5) {
            labels[5] = 'Other';
            data[5] = tagStats[i].frequency;
        } else {
            data[5] += tagStats[i].frequency;
        }
    }

    const ctx = canvas.getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: '# of Tags Used',
                data: data,
                backgroundColor: [
                    defaultColor[0],
                    defaultColor[1],
                    defaultColor[2],
                    defaultColor[3],
                    defaultColor[4],
                    defaultColor[5]
                ],
                hoverBackgroundColor: [
                    hoverColor[0],
                    hoverColor[1],
                    hoverColor[2],
                    hoverColor[3],
                    hoverColor[4],
                    hoverColor[5]
                ],
                borderWidth: 0,
            }]
        },
        options: {
            legend: {
                position: 'right'
            }
        }
    });
}


