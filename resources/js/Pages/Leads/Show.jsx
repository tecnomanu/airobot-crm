import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ScrollArea } from "@/components/ui/scroll-area";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import {
    Bot,
    CheckCircle2,
    Clock,
    Mail,
    MessageCircle,
    Phone,
    PlayCircle,
    User,
    XCircle,
} from "lucide-react";
import { useState } from "react";

export default function LeadShow({ lead }) {
    const [aiAgentActive, setAiAgentActive] = useState(lead.ai_agent_active ?? true);

    const { data, setData, put, processing } = useForm({
        manual_classification: lead.manual_classification || "",
        decision_notes: lead.decision_notes || "",
    });

    const getStatusStyle = (status) => {
        const styles = {
            new: "bg-blue-100 border-blue-200 text-blue-700",
            inbox: "bg-blue-100 border-blue-200 text-blue-700",
            pending: "bg-yellow-100 border-yellow-200 text-yellow-700",
            qualifying: "bg-yellow-100 border-yellow-200 text-yellow-700",
            in_progress: "bg-purple-100 border-purple-200 text-purple-700",
            active_pipeline: "bg-purple-100 border-purple-200 text-purple-700",
            contacted: "bg-indigo-100 border-indigo-200 text-indigo-700",
            sales_ready: "bg-emerald-100 border-emerald-200 text-emerald-700 font-bold",
            converted: "bg-green-100 border-green-200 text-green-700",
            closed: "bg-gray-100 border-gray-200 text-gray-700",
            lost: "bg-red-100 border-red-200 text-red-700",
            invalid: "bg-red-100 border-red-200 text-red-700",
        };
        return styles[status] || "bg-gray-100 border-gray-200 text-gray-700";
    };

    const formatDate = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toLocaleDateString("en-GB");
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toLocaleString("en-GB", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    const handleSaveDecision = (e) => {
        e.preventDefault();
        put(route("leads.update", lead.id), {
            preserveScroll: true,
        });
    };

    const toggleAiAgent = () => {
        setAiAgentActive(!aiAgentActive);
    };

    // Combine messages and calls into timeline
    const getActivityTimeline = () => {
        const activities = [];

        if (lead.messages && lead.messages.length > 0) {
            lead.messages.forEach((msg) => {
                activities.push({
                    type: "message",
                    id: `msg-${msg.id}`,
                    direction: msg.direction,
                    content: msg.content,
                    created_at: msg.created_at,
                    sender: msg.direction === "inbound" ? (lead.name || "Lead") : "AI Agent",
                });
            });
        }

        if (lead.calls && lead.calls.length > 0) {
            lead.calls.forEach((call) => {
                activities.push({
                    type: "call",
                    id: `call-${call.id}`,
                    duration: call.duration_seconds,
                    status: call.status,
                    summary: call.summary || call.notes,
                    recording_url: call.recording_url,
                    created_at: call.call_date || call.created_at,
                });
            });
        }

        return activities.sort(
            (a, b) => new Date(b.created_at) - new Date(a.created_at)
        );
    };

    const timeline = getActivityTimeline();

    return (
        <AppLayout
            stretch
            header={{
                title: (
                    <div className="flex items-center gap-2">
                        <span className="text-base font-bold">{lead.name || "Unknown"}</span>
                        <span className={`text-[10px] px-2 py-0.5 rounded-full border ${getStatusStyle(lead.status)}`}>
                            {lead.status_label?.toUpperCase() || lead.status?.replace("_", " ").toUpperCase() || "NEW"}
                        </span>
                    </div>
                ),
                subtitle: (
                    <span className="text-xs text-gray-500">
                        Added on {formatDate(lead.created_at)} via{" "}
                        <span className="font-semibold">{lead.source_label || lead.source || "Unknown"}</span>
                    </span>
                ),
                backButton: {
                    href: route("leads.index"),
                },
                actions: (
                    <div className="flex items-center gap-2">
                        {/* AI Toggle */}
                        <div className="flex items-center gap-2 bg-white border border-gray-200 px-2 py-1 rounded-lg shadow-sm">
                            <div className={`w-2 h-2 rounded-full ${aiAgentActive ? "bg-green-500 animate-pulse" : "bg-orange-500"}`} />
                            <span className="text-xs font-medium text-gray-700">
                                AI Agent: {aiAgentActive ? "ACTIVE" : "PAUSED"}
                            </span>
                            <button
                                onClick={toggleAiAgent}
                                className={`text-[10px] font-bold px-1.5 py-0.5 rounded text-white ${
                                    aiAgentActive ? "bg-red-500 hover:bg-red-600" : "bg-green-600 hover:bg-green-700"
                                }`}
                            >
                                {aiAgentActive ? "PAUSE" : "ACTIVATE"}
                            </button>
                        </div>

                        <Link href={route("messages.index", { lead_id: lead.id })}>
                            <Button size="sm" className="h-7 text-xs bg-indigo-600 hover:bg-indigo-700">
                                <MessageCircle className="h-3.5 w-3.5 mr-1.5" />
                                Open Chat
                            </Button>
                        </Link>
                    </div>
                ),
            }}
        >
            <Head title={`Lead - ${lead.name || lead.phone}`} />

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 h-[calc(100vh-6rem)]">
                {/* Left Column: Info & Metadata */}
                <div className="space-y-4 overflow-auto">
                    {/* Contact Info */}
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <h3 className="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-100">
                            Contact Details
                        </h3>
                        <div className="space-y-3">
                            <div className="flex items-center gap-2 text-gray-700">
                                <Phone className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">{lead.phone}</span>
                            </div>
                            <div className="flex items-center gap-2 text-gray-700">
                                <Mail className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">{lead.email || "No email provided"}</span>
                            </div>
                            <div className="flex items-center gap-2 text-gray-700">
                                <User className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">
                                    Client: {lead.campaign?.client?.name || lead.client?.name || "Unassigned"}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Technical Metadata */}
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <h3 className="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-100">
                            System Metadata
                        </h3>
                        <div className="text-xs space-y-2">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Campaign ID</span>
                                <span className="font-mono text-gray-800">
                                    {lead.campaign?.id || lead.campaign_id || "N/A"}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Lead ID</span>
                                <span className="font-mono text-gray-800">
                                    {lead.id?.substring(0, 8) || lead.id}
                                </span>
                            </div>
                            {lead.option_selected && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">IvrSelection</span>
                                    <span className="font-mono text-gray-800">{lead.option_selected}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Outcome & Decision */}
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <h3 className="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-100">
                            Outcome & Decision
                        </h3>
                        <form onSubmit={handleSaveDecision} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">
                                    Manual Classification
                                </label>
                                <Select
                                    value={data.manual_classification}
                                    onValueChange={(value) => setData("manual_classification", value)}
                                >
                                    <SelectTrigger className="h-8 text-xs border-gray-300">
                                        <SelectValue placeholder="Select status..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="interested">Interested</SelectItem>
                                        <SelectItem value="not_interested">Not Interested</SelectItem>
                                        <SelectItem value="callback">Callback Requested</SelectItem>
                                        <SelectItem value="qualified">Qualified</SelectItem>
                                        <SelectItem value="disqualified">Disqualified</SelectItem>
                                        <SelectItem value="no_answer">No Answer</SelectItem>
                                        <SelectItem value="wrong_number">Wrong Number</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <textarea
                                className="w-full border border-gray-300 rounded-md shadow-sm text-xs p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Add notes about the decision..."
                                rows={3}
                                value={data.decision_notes}
                                onChange={(e) => setData("decision_notes", e.target.value)}
                            />
                            <Button
                                type="submit"
                                size="sm"
                                className="w-full h-8 text-xs bg-gray-800 hover:bg-gray-900"
                                disabled={processing}
                            >
                                Save Decision
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Right Column: Timeline / History */}
                <div className="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col h-full overflow-hidden">
                    <div className="px-4 py-3 border-b border-gray-100">
                        <h3 className="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Activity Timeline
                        </h3>
                    </div>

                    <ScrollArea className="flex-1 px-4 py-3">
                        {timeline.length === 0 ? (
                            <div className="text-center text-gray-400 py-10 text-sm">
                                No activity recorded yet.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {timeline.map((item) => (
                                    <div key={item.id} className="flex gap-3">
                                        <div className="flex flex-col items-center">
                                            <div
                                                className={`w-7 h-7 rounded-full flex items-center justify-center z-10 ${
                                                    item.type === "call"
                                                        ? "bg-purple-100 text-purple-600"
                                                        : item.sender === "AI Agent"
                                                        ? "bg-green-100 text-green-600"
                                                        : "bg-gray-200 text-gray-600"
                                                }`}
                                            >
                                                {item.type === "call" ? (
                                                    <Phone className="h-3.5 w-3.5" />
                                                ) : item.sender === "AI Agent" ? (
                                                    <Bot className="h-3.5 w-3.5" />
                                                ) : (
                                                    <User className="h-3.5 w-3.5" />
                                                )}
                                            </div>
                                            <div className="w-0.5 flex-1 bg-gray-100 -mt-1" />
                                        </div>
                                        <div className="flex-1 pb-4">
                                            <div className="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                                <div className="flex justify-between items-start mb-1.5">
                                                    <span className="font-semibold text-xs text-gray-900">
                                                        {item.type === "call"
                                                            ? "Outbound Call"
                                                            : item.sender}
                                                    </span>
                                                    <span className="text-[10px] text-gray-400">
                                                        {formatDateTime(item.created_at)}
                                                    </span>
                                                </div>

                                                {item.type === "call" ? (
                                                    <div className="space-y-1.5">
                                                        <div className="flex items-center gap-3 text-xs text-gray-600">
                                                            <span className="flex items-center gap-1">
                                                                <Clock className="h-3 w-3" />
                                                                {item.duration}s
                                                            </span>
                                                            <span
                                                                className={`flex items-center gap-1 font-medium ${
                                                                    item.status === "COMPLETED" || item.status === "completed"
                                                                        ? "text-green-600"
                                                                        : "text-red-500"
                                                                }`}
                                                            >
                                                                {item.status === "COMPLETED" || item.status === "completed" ? (
                                                                    <CheckCircle2 className="h-3 w-3" />
                                                                ) : (
                                                                    <XCircle className="h-3 w-3" />
                                                                )}
                                                                {item.status?.toUpperCase()}
                                                            </span>
                                                        </div>
                                                        {item.summary && (
                                                            <p className="text-xs text-gray-600 italic border-l-2 border-purple-300 pl-2">
                                                                "{item.summary}"
                                                            </p>
                                                        )}
                                                        {item.recording_url && (
                                                            <a
                                                                href={item.recording_url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center gap-1 text-[10px] bg-white border border-gray-200 px-1.5 py-0.5 rounded shadow-sm hover:text-indigo-600"
                                                            >
                                                                <PlayCircle className="h-3 w-3" />
                                                                Play Recording
                                                            </a>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <p className="text-xs text-gray-700 whitespace-pre-wrap">
                                                        {item.content}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </ScrollArea>
                </div>
            </div>
        </AppLayout>
    );
}
