import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

export default function WebhookSettingsTab({ data, setData }) {
    return (
        <div className="space-y-2">
            <div className="space-y-1.5">
                <Label htmlFor="webhook_url" className="text-xs">
                    Webhook URL
                </Label>
                <Input
                    id="webhook_url"
                    type="url"
                    value={data.webhook_url}
                    onChange={(e) => setData("webhook_url", e.target.value)}
                    className="h-7 text-xs"
                    placeholder="https://..."
                />
            </div>

            <div className="space-y-1.5">
                <Label className="text-xs">Timeout (ms)</Label>
                <Input
                    type="number"
                    value={data.webhook_timeout_ms}
                    onChange={(e) =>
                        setData(
                            "webhook_timeout_ms",
                            parseInt(e.target.value) || 10000
                        )
                    }
                    className="h-7 text-xs"
                />
            </div>
        </div>
    );
}

