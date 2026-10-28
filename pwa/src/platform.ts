export type InstallMode = 'installed' | 'prompt' | 'ios' | 'none';

/** iPhone/iPod/iPad; iPadOS 13+ memakai UA Macintosh, dibedakan dari layar sentuh. */
export function isIos(ua: string, maxTouchPoints: number): boolean {
    return /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && maxTouchPoints > 1);
}

/** Cara memasang: sudah terpasang, dialog bawaan (Android/Chrome), petunjuk manual (iOS), atau tidak bisa. */
export function installMode(standalone: boolean, hasPrompt: boolean, ios: boolean): InstallMode {
    return standalone ? 'installed' : hasPrompt ? 'prompt' : ios ? 'ios' : 'none';
}
