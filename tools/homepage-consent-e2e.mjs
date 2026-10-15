/**
 * Homepage cookie-consent + beacon E2E (Edge via puppeteer-core).
 * Usage:
 *   node tools/homepage-consent-e2e.mjs
 *   node tools/homepage-consent-e2e.mjs --mobile
 *   node tools/homepage-consent-e2e.mjs --url=http://127.0.0.1:8000/
 */
import puppeteer from 'puppeteer-core';

const args = process.argv.slice(2);
const mobile = args.includes('--mobile');
const urlArg = args.find((a) => a.startsWith('--url='));
const HOME = urlArg
    ? urlArg.slice('--url='.length)
    : (process.env.CONSENT_E2E_URL || 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/');
const EDGE = process.env.EDGE_PATH
    || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const MOBILE_VIEWPORTS = [
    { name: '320', width: 320, height: 568, deviceScaleFactor: 2, isMobile: true, hasTouch: true },
    { name: '360', width: 360, height: 740, deviceScaleFactor: 3, isMobile: true, hasTouch: true },
    { name: '375', width: 375, height: 812, deviceScaleFactor: 3, isMobile: true, hasTouch: true },
    { name: '390', width: 390, height: 844, deviceScaleFactor: 3, isMobile: true, hasTouch: true },
    { name: '430', width: 430, height: 932, deviceScaleFactor: 3, isMobile: true, hasTouch: true },
];

const browser = await puppeteer.launch({
    executablePath: EDGE,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

async function assertBannerVisible(page, label) {
    const acceptSelector = '#agCookieBanner [data-action="accept-all"]';
    await page.waitForSelector(acceptSelector, { visible: true, timeout: 30000 });

    const state = await page.evaluate(() => {
        const banner = document.getElementById('agCookieBanner');
        if (!banner) {
            return null;
        }
        const style = window.getComputedStyle(banner);
        const rect = banner.getBoundingClientRect();
        return {
            classes: banner.className,
            visibility: style.visibility,
            opacity: style.opacity,
            display: style.display,
            ariaHidden: banner.getAttribute('aria-hidden'),
            width: Math.round(rect.width),
            height: Math.round(rect.height),
            top: Math.round(rect.top),
            bottom: Math.round(rect.bottom),
            overflowX: document.documentElement.scrollWidth > window.innerWidth + 1,
        };
    });

    if (!state || state.visibility === 'hidden' || Number(state.opacity) === 0 || state.height < 40) {
        throw new Error(`[${label}] Cookie banner not visibly on screen: ${JSON.stringify(state)}`);
    }
    if (state.overflowX) {
        throw new Error(`[${label}] Horizontal overflow while cookie banner is open`);
    }

    console.log(`[${label}] Cookie banner visible`, state);
    return acceptSelector;
}

async function runDesktopPass() {
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
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

    console.log('Loading homepage (desktop):', HOME);
    await page.goto(HOME, { waitUntil: 'networkidle2', timeout: 60000 });

    const acceptSelector = await assertBannerVisible(page, 'desktop');
    console.log('Cookie banner visible — clicking Accept All');
    await page.click(acceptSelector);

    for (let i = 0; i < 20 && !beaconResult; i++) {
        await new Promise((r) => setTimeout(r, 500));
    }

    const consent = await page.evaluate(() => localStorage.getItem('ag_ikenebgu_cookie_consent'));
    const endpoint = await page.evaluate(() => (window.AG_SITE_TRAFFIC && window.AG_SITE_TRAFFIC.endpoint) || '');

    await page.reload({ waitUntil: 'networkidle2', timeout: 60000 });
    await new Promise((r) => setTimeout(r, 2000));
    const stillHidden = await page.evaluate(() => {
        const banner = document.getElementById('agCookieBanner');
        if (!banner) {
            return true;
        }
        return banner.getAttribute('aria-hidden') === 'true'
            && !banner.classList.contains('is-visible')
            && !banner.classList.contains('is-breaking');
    });

    await page.close();

    return {
        endpoint,
        consentSaved: consent !== null,
        consent,
        beacon: beaconResult,
        respectedAfterReload: stillHidden,
    };
}

async function runMobilePass(viewport) {
    const page = await browser.newPage();
    await page.setViewport(viewport);
    await page.evaluateOnNewDocument(() => {
        localStorage.clear();
        sessionStorage.clear();
    });

    console.log(`Loading homepage (mobile ${viewport.name}):`, HOME);
    await page.goto(HOME, { waitUntil: 'networkidle2', timeout: 60000 });

    const acceptSelector = await assertBannerVisible(page, `mobile-${viewport.name}`);

    await page.click('#agCookieBanner [data-action="customize"]');
    await page.waitForSelector('#agCookieModal.is-open', { visible: true, timeout: 10000 });
    await page.click('#agCookieModal [data-action="save-prefs"]');
    await page.waitForFunction(() => {
        const modal = document.getElementById('agCookieModal');
        const consent = localStorage.getItem('ag_ikenebgu_cookie_consent');
        return modal && !modal.classList.contains('is-open') && !!consent;
    }, { timeout: 10000 });

    await page.reload({ waitUntil: 'networkidle2', timeout: 60000 });
    await new Promise((r) => setTimeout(r, 2000));
    const respected = await page.evaluate(() => {
        const banner = document.getElementById('agCookieBanner');
        return !!localStorage.getItem('ag_ikenebgu_cookie_consent')
            && !!banner
            && banner.getAttribute('aria-hidden') === 'true'
            && !banner.classList.contains('is-visible')
            && !banner.classList.contains('is-breaking');
    });

    await page.close();
    return { viewport: viewport.name, prefsSavedAndRespected: respected, acceptSelectorUsed: !!acceptSelector };
}

let failed = false;

if (mobile) {
    const results = [];
    for (const viewport of MOBILE_VIEWPORTS) {
        try {
            results.push(await runMobilePass(viewport));
        } catch (error) {
            failed = true;
            results.push({ viewport: viewport.name, error: String(error?.message || error) });
        }
    }
    console.log('--- MOBILE RESULT ---');
    console.log(JSON.stringify(results, null, 2));
} else {
    try {
        const result = await runDesktopPass();
        console.log('--- RESULT ---');
        console.log(JSON.stringify(result, null, 2));
        if (!result.beacon || result.beacon.status !== 200 || !result.beacon.body?.success || !result.respectedAfterReload) {
            failed = true;
        }
    } catch (error) {
        failed = true;
        console.error(String(error?.message || error));
    }
}

await browser.close();
process.exit(failed ? 1 : 0);
