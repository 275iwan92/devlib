import assert from 'node:assert/strict';
import { test } from 'node:test';
import { installMode, isIos } from './platform.ts';

const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1';
const IPAD_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/18.0 Safari/605.1.15';
const ANDROID = 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 Chrome/141.0.0.0 Mobile Safari/537.36';

test('isIos: iPhone, iPad ber-UA Mac (sentuh), bukan Mac sungguhan atau Android', () => {
    assert.equal(isIos(IPHONE, 5), true);
    assert.equal(isIos(IPAD_MAC, 5), true);
    assert.equal(isIos(IPAD_MAC, 0), false);
    assert.equal(isIos(ANDROID, 5), false);
});

test('installMode: terpasang menang, lalu dialog bawaan, lalu iOS, lalu none', () => {
    assert.equal(installMode(true, true, true), 'installed');
    assert.equal(installMode(false, true, true), 'prompt');
    assert.equal(installMode(false, false, true), 'ios');
    assert.equal(installMode(false, false, false), 'none');
});
