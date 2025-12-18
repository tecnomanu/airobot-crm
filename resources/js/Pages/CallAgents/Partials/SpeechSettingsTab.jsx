import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

export default function SpeechSettingsTab({ data, setData }) {
    return (
        <div className="space-y-2">
            <div className="space-y-1.5">
                <Label className="text-xs">Transcription Mode</Label>
                <Select
                    value={data.stt_mode}
                    onValueChange={(value) => setData("stt_mode", value)}
                >
                    <SelectTrigger className="h-7 text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="fast">Optimize for speed</SelectItem>
                        <SelectItem value="accurate">
                            Optimize for accuracy
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-1.5">
                <Label className="text-xs">Denoising Mode</Label>
                <Select
                    value={data.denoising_mode}
                    onValueChange={(value) =>
                        setData("denoising_mode", value)
                    }
                >
                    <SelectTrigger className="h-7 text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="noise-cancellation">
                            Remove noise
                        </SelectItem>
                        <SelectItem value="noise-and-background-speech-cancellation">
                            Remove noise + background speech
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-1.5">
                <Label className="text-xs">Vocabulary</Label>
                <Select
                    value={data.vocab_specialization}
                    onValueChange={(value) =>
                        setData("vocab_specialization", value)
                    }
                >
                    <SelectTrigger className="h-7 text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="general">General</SelectItem>
                        <SelectItem value="medical">Medical</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1.5">
                    <Label className="text-xs">Voice Speed</Label>
                    <Input
                        type="number"
                        step="0.1"
                        min="0.5"
                        max="2.0"
                        value={data.voice_speed}
                        onChange={(e) =>
                            setData(
                                "voice_speed",
                                parseFloat(e.target.value) || 1.0
                            )
                        }
                        className="h-7 text-xs"
                    />
                </div>
                <div className="space-y-1.5">
                    <Label className="text-xs">Voice Temp</Label>
                    <Input
                        type="number"
                        step="0.1"
                        min="0"
                        max="1"
                        value={data.voice_temperature}
                        onChange={(e) =>
                            setData(
                                "voice_temperature",
                                parseFloat(e.target.value) || 0.7
                            )
                        }
                        className="h-7 text-xs"
                    />
                </div>
            </div>
        </div>
    );
}

