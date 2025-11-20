import { MessageSquare, Phone } from "lucide-react";

export default function TestPanel({ activeTab, onTabChange }) {
    return (
        <div className="p-2 space-y-2">
            <div className="flex gap-1 border-b">
                <button
                    onClick={() => onTabChange("chat")}
                    className={`px-2 py-1 text-xs font-medium border-b-2 transition-colors ${
                        activeTab === "chat"
                            ? "border-primary text-primary"
                            : "border-transparent text-muted-foreground"
                    }`}
                >
                    Test Chat
                </button>
                <button
                    onClick={() => onTabChange("audio")}
                    className={`px-2 py-1 text-xs font-medium border-b-2 transition-colors ${
                        activeTab === "audio"
                            ? "border-primary text-primary"
                            : "border-transparent text-muted-foreground"
                    }`}
                >
                    Test Audio
                </button>
            </div>

            {activeTab === "chat" && (
                <div className="space-y-2">
                    <div className="h-64 border rounded-lg p-3 bg-background">
                        <div className="flex flex-col items-center justify-center h-full text-center text-sm text-muted-foreground">
                            <MessageSquare className="h-8 w-8 mb-2 opacity-50" />
                            <p>Test Chat</p>
                            <p className="text-xs mt-1">Coming Soon</p>
                        </div>
                    </div>
                </div>
            )}

            {activeTab === "audio" && (
                <div className="space-y-2">
                    <div className="h-64 border rounded-lg p-3 bg-background">
                        <div className="flex flex-col items-center justify-center h-full text-center text-sm text-muted-foreground">
                            <Phone className="h-8 w-8 mb-2 opacity-50" />
                            <p>Test Audio</p>
                            <p className="text-xs mt-1">Coming Soon</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

