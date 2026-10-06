import './bootstrap';

import Alpine from 'alpinejs';
import { registerVoiceAssistant } from './voice-assistant';
import { registerFlyerGenerator } from './flyer-generator';
import { registerSiteVoice } from './site-voice';
import { registerAdminStats } from './admin-stats';
import { pickBestSpanishVoice, readVoicePrefs } from './voice-quality';

window.Alpine = Alpine;
window.sonaraPickVoice = pickBestSpanishVoice;
window.sonaraVoicePrefs = readVoicePrefs;

registerVoiceAssistant();
registerFlyerGenerator();
registerSiteVoice();
registerAdminStats();

Alpine.start();
