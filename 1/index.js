const { google } = require('googleapis');
const express = require('express');
const app = express();

const port = process.env.PORT || 8080;
app.listen(port, () => {
    console.log('Listening!', port);
});

app.get('/', async (req, res) => {
    res.send('Events-API-homepage!');
});

app.get('/events', async (req, res) => {
    const events = await getRow();
    let retval;
    if (events) {
        retval = {
            status: 'success',
            data: { events: events }
        }
    } else {
        retval = {
            status: 'error',
            data: { events: 'nothing' }
        }
    }
    res.setHeader('content-type', 'application/json');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'OPTIONS,GET');
    res.send(JSON.stringify(retval));
});

async function getRow() {
    const auth = await google.auth.getClient({
        scopes: ['https://www.googleapis.com/auth/spreadsheets']
    });

    const api = google.sheets({ version: 'v4', auth });
    const response = await api.spreadsheets.values.get({
        spreadsheetId: '1e34GQAvfEeJ-iUtQzsCSI_UaS-6fHK_26aYbbokyzj0', // This is the spreadsheet ID, and this one is random/garbled
        range: 'Events!A:F'
    });

    // For your own spreadsheet and range, print `response` and see what would you want to do with the returned object/array

    let frow = true;
    let past = [];
    let upcoming = [];
    for (let row of response.data.values) {

        // Skip first row (Spreadsheet headers)
	if(frow) {
	    frow = false;
	    continue;
	}
	if(row[5] == 'Disabled') {
	    continue;
	}
	if(row[3] == 'Past') {
            past.push({
                title: row[0],
                speaker: row[2],
                link: row[4]
            });
	} else {
	    upcoming.push({
		title: row[0],
		speaker: row[2],
		link: row[4]
	    });
	}
    }
    return {past: past, upcoming: upcoming};
}

