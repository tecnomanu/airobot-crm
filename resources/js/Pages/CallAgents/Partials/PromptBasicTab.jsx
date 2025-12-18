import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import LanguageSelect from "@/Components/CallAgent/LanguageSelect";
import WelcomeMessageSelect from "@/Components/CallAgent/WelcomeMessageSelect";

export default function PromptBasicTab({ data, setData, errors }) {
    return (
        <div className="space-y-2">
            <div className="space-y-1.5">
                <Label htmlFor="agent_name" className="text-xs">
                    Nombre *
                </Label>
                <Input
                    id="agent_name"
                    value={data.agent_name}
                    onChange={(e) => setData("agent_name", e.target.value)}
                    className="h-8 text-xs"
                    placeholder="Ej: Agente de Ventas"
                />
                {errors.agent_name && (
                    <p className="text-xs text-red-500">{errors.agent_name}</p>
                )}
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="voice_id" className="text-xs">
                    Voice ID *
                </Label>
                <Input
                    id="voice_id"
                    value={data.voice_id}
                    onChange={(e) => setData("voice_id", e.target.value)}
                    className="h-8 text-xs"
                    placeholder="11labs-Adrian"
                />
                {errors.voice_id && (
                    <p className="text-xs text-red-500">{errors.voice_id}</p>
                )}
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="language" className="text-xs">
                    Idioma *
                </Label>
                <LanguageSelect
                    value={data.language}
                    onValueChange={(value) => setData("language", value)}
                    className="h-8 text-xs"
                />
                {errors.language && (
                    <p className="text-xs text-red-500">{errors.language}</p>
                )}
            </div>

            <div className="space-y-1.5">
                <WelcomeMessageSelect
                    value={data.welcome_message_mode || (data.first_message ? "ai_speaks_first" : "user_speaks_first")}
                    onValueChange={(mode) => {
                        setData("welcome_message_mode", mode);
                        // Si se selecciona "user_speaks_first", limpiar el mensaje
                        if (mode === "user_speaks_first") {
                            setData("first_message", "");
                        }
                    }}
                    className="h-8 text-xs"
                />
            </div>
        </div>
    );
}

