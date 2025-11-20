import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function CallSettingsTab({ data, setData }) {
    return (
        <div className="space-y-2">
            <div className="space-y-1.5">
                <Label className="text-xs">End Call on Silence (min)</Label>
                <Input
                    type="number"
                    value={Math.round(
                        (data.end_call_after_silence_ms || 600000) / 60000
                    )}
                    onChange={(e) =>
                        setData(
                            "end_call_after_silence_ms",
                            (parseInt(e.target.value) || 10) * 60000
                        )
                    }
                    className="h-7 text-xs"
                />
            </div>

            <div className="space-y-1.5">
                <Label className="text-xs">Max Call Duration (min)</Label>
                <Input
                    type="number"
                    value={Math.round(
                        (data.max_call_duration_ms || 3600000) / 60000
                    )}
                    onChange={(e) =>
                        setData(
                            "max_call_duration_ms",
                            (parseInt(e.target.value) || 60) * 60000
                        )
                    }
                    className="h-7 text-xs"
                />
            </div>

            <div className="space-y-1.5">
                <Label className="text-xs">Ring Duration (sec)</Label>
                <Input
                    type="number"
                    value={Math.round(
                        (data.ring_duration_ms || 30000) / 1000
                    )}
                    onChange={(e) =>
                        setData(
                            "ring_duration_ms",
                            (parseInt(e.target.value) || 30) * 1000
                        )
                    }
                    className="h-7 text-xs"
                />
            </div>
        </div>
    );
}

