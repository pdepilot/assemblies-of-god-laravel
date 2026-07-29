/**
 * Homepage cookie-consent + beacon E2E (Edge via puppeteer-core).
 * Usage: node tools/homepage-consent-e2e.mjs
 */
import puppeteer from 'puppeteer-core';

const HOME = 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/';
const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const browser = await puppeteer.launch({
    executablePath: EDGE,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const page = await browser.newPage();

await page.evaluateOnNewDocument(() => {
    localStorage.clear();
    sessionStorage.clear();
});

let beaconResult = null;
page.on('response', async (response) => {
    const url = response.url();
    if (!url.includes('/api/track-traffic') || response.request().method() !== 'POST') {
        return;
    }
    try {
        beaconResult = {
            url,
            status: response.status(),
            body: await response.json(),
        };
    } catch {
        beaconResult = { url, status: response.status(), body: null };
    }
});

console.log('Loading homepage:', HOME);
await page.goto(HOME, { waitUntil: 'networkidle2', timeout: 60000 });

const acceptSelector = '#agCookieBanner [data-action="accept-all"]';
await page.waitForSelector(acceptSelector, { visible: true, timeout: 30000 });
console.log('Cookie banner visible — clicking Accept All');

await page.click(acceptSelector);

for (let i = 0; i < 20 && !beaconResult; i++) {
    await new Promise((r) => setTimeout(r, 500));
}

const consent = await page.evaluate(() => localStorage.getItem('ag_ikenebgu_cookie_consent'));
const endpoint = await page.evaluate(() => (window.AG_SITE_TRAFFIC && window.AG_SITE_TRAFFIC.endpoint) || '');

console.log('--- RESULT ---');
console.log(JSON.stringify({
    endpoint,
    consentSaved: consent !== null,
    consent,
    beacon: beaconResult,
}, null, 2));

await browser.close();

if (!beaconResult || beaconResult.status !== 200 || !beaconResult.body?.success) {
    process.exit(1);
}
