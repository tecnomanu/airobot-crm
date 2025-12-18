import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

// Lista de idiomas soportados por Retell AI con códigos ISO
const RETELL_LANGUAGES = [
    // Español
    { code: "es-ES", name: "Español (España)", flag: "🇪🇸" },
    { code: "es-419", name: "Español (Latinoamérica)", flag: "🇲🇽" },
    { code: "es-MX", name: "Español (México)", flag: "🇲🇽" },
    { code: "es-AR", name: "Español (Argentina)", flag: "🇦🇷" },
    { code: "es-CO", name: "Español (Colombia)", flag: "🇨🇴" },
    { code: "es-CL", name: "Español (Chile)", flag: "🇨🇱" },

    // Inglés
    { code: "en-US", name: "English (US)", flag: "🇺🇸" },
    { code: "en-GB", name: "English (UK)", flag: "🇬🇧" },
    { code: "en-AU", name: "English (Australia)", flag: "🇦🇺" },
    { code: "en-CA", name: "English (Canada)", flag: "🇨🇦" },
    { code: "en-IN", name: "English (India)", flag: "🇮🇳" },
    { code: "en-NZ", name: "English (New Zealand)", flag: "🇳🇿" },

    // Otros idiomas principales
    { code: "fr-FR", name: "Français", flag: "🇫🇷" },
    { code: "de-DE", name: "Deutsch", flag: "🇩🇪" },
    { code: "it-IT", name: "Italiano", flag: "🇮🇹" },
    { code: "pt-BR", name: "Português (Brasil)", flag: "🇧🇷" },
    { code: "pt-PT", name: "Português (Portugal)", flag: "🇵🇹" },
    { code: "zh-CN", name: "中文 (简体)", flag: "🇨🇳" },
    { code: "zh-TW", name: "中文 (繁體)", flag: "🇹🇼" },
    { code: "ja-JP", name: "日本語", flag: "🇯🇵" },
    { code: "ko-KR", name: "한국어", flag: "🇰🇷" },
    { code: "nl-NL", name: "Nederlands", flag: "🇳🇱" },
    { code: "pl-PL", name: "Polski", flag: "🇵🇱" },
    { code: "cs-CZ", name: "Čeština", flag: "🇨🇿" },
    { code: "ru-RU", name: "Русский", flag: "🇷🇺" },
    { code: "ar-SA", name: "العربية", flag: "🇸🇦" },
    { code: "hi-IN", name: "हिन्दी", flag: "🇮🇳" },
];

export default function LanguageSelect({ value, onValueChange, className }) {
    const selectedLanguage = RETELL_LANGUAGES.find(
        (lang) => lang.code === value
    );

    return (
        <Select value={value} onValueChange={onValueChange}>
            <SelectTrigger className={className}>
                {selectedLanguage ? (
                    <div className="flex items-center gap-1.5 w-full">
                        <span className="text-sm flex-shrink-0">
                            {selectedLanguage.flag}
                        </span>
                        <span className="text-xs flex-1 text-left">
                            {selectedLanguage.name}
                        </span>
                    </div>
                ) : (
                    <SelectValue placeholder="Seleccionar idioma" />
                )}
            </SelectTrigger>
            <SelectContent className="max-h-[300px]">
                {RETELL_LANGUAGES.map((language) => (
                    <SelectItem key={language.code} value={language.code}>
                        <div className="flex items-center gap-2 w-full">
                            <span>{language.flag}</span>
                            <span className="flex-1 text-xs">
                                {language.name}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {language.code}
                            </span>
                        </div>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
