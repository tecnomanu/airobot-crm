import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { ScrollArea } from "@/Components/ui/scroll-area";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import {
    Bot,
    Brain,
    CheckCircle2,
    Clock,
    ExternalLink,
    Mail,
    MessageCircle,
    Phone,
    PlayCircle,
    Rocket,
    Send,
    Sparkles,
    User,
    UserPlus,
    XCircle,
} from "lucide-react";
import { useState } from "react";

export default function LeadShow({ lead }) {
    const [aiAgentActive, setAiAgentActive] = useState(lead.ai_agent_active ?? true);

    const { data, setData, put, processing } = useForm({
        manual_classification: lead.manual_classification || "",
        decision_notes: lead.decision_notes || "",
    });

    // Calculate display status based on intention_status
    const getDisplayStatus = () => {
        if (lead.intention_status === "finalized" && lead.intention === "interested") {
            return { status: "sales_ready", label: "SALES READY" };
        }
        if (lead.intention_status === "finalized" && lead.intention === "not_interested") {
            return { status: "closed", label: "NOT INTERESTED" };
        }
        if (lead.intention_status === "pending") {
            return { status: "qualifying", label: "QUALIFYING" };
        }
        return { 
            status: lead.status, 
            label: lead.status_label?.toUpperCase() || lead.status?.replace("_", " ").toUpperCase() || "NEW" 
        };
    };

    const displayStatus = getDisplayStatus();

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

    // Combine messages, calls, and system events into timeline
    const getActivityTimeline = () => {
        const activities = [];

        // 1. Lead creation event
        activities.push({
            type: "system",
            id: "sys-created",
            event: "lead_created",
            content: `Lead creado vía ${lead.source_label || lead.source || "Desconocido"}`,
            created_at: lead.created_at,
            icon: "user_plus",
        });

        // 2. Campaign execution event (if automation was processed)
        if (lead.automation_status === "completed" || lead.automation_status === "processing") {
            activities.push({
                type: "system",
                id: "sys-campaign",
                event: "campaign_executed",
                content: `Campaña "${lead.campaign?.name || "Desconocida"}" ejecutada${lead.option_selected ? ` (Opción ${lead.option_selected})` : ""}`,
                created_at: lead.last_automation_run_at || lead.updated_at,
                icon: "rocket",
            });
        }

        // 3. Messages
        if (lead.messages && lead.messages.length > 0) {
            lead.messages.forEach((msg) => {
                const isOutbound = msg.direction === "outbound";
                activities.push({
                    type: "message",
                    id: `msg-${msg.id}`,
                    direction: msg.direction,
                    content: msg.content,
                    created_at: msg.created_at,
                    sender: isOutbound ? "Agente IA" : (lead.name || "Lead"),
                    channel: msg.channel || "whatsapp",
                    // For outbound, show the source phone if available
                    sourcePhone: isOutbound ? (lead.contact_source_phone || null) : null,
                });
            });
        }

        // 4. Calls
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

        // 5. LLM decision event (if intention was determined by AI)
        if (lead.intention && lead.intention_origin === "whatsapp") {
            activities.push({
                type: "system",
                id: "sys-llm-decision",
                event: "llm_decision",
                content: `IA analizó respuesta: "${lead.intention === "interested" ? "Interesado" : "No Interesado"}"`,
                created_at: lead.intention_decided_at || lead.updated_at,
                icon: "brain",
            });
        }

        // 6. Sales Ready event (if finalized as interested)
        if (lead.intention_status === "finalized" && lead.intention === "interested") {
            activities.push({
                type: "system",
                id: "sys-sales-ready",
                event: "sales_ready",
                content: "Lead listo para ventas",
                created_at: lead.intention_decided_at || lead.updated_at,
                icon: "sparkles",
            });
        }

        // Sort by date descending (newest first)
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
                        <span className={`text-[10px] px-2 py-0.5 rounded-full border ${getStatusStyle(displayStatus.status)}`}>
                            {displayStatus.label}
                        </span>
                    </div>
                ),
                subtitle: (
                    <span className="text-xs text-gray-500">
                        Agregado el {formatDate(lead.created_at)} vía{" "}
                        <span className="font-semibold">{lead.source_label || lead.source || "Desconocido"}</span>
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
                                Agente IA: {aiAgentActive ? "ACTIVO" : "PAUSADO"}
                            </span>
                            <button
                                onClick={toggleAiAgent}
                                className={`text-[10px] font-bold px-1.5 py-0.5 rounded text-white ${
                                    aiAgentActive ? "bg-red-500 hover:bg-red-600" : "bg-green-600 hover:bg-green-700"
                                }`}
                            >
                                {aiAgentActive ? "PAUSAR" : "ACTIVAR"}
                            </button>
                        </div>

                        <Link href={route("messages.index", { lead_id: lead.id })}>
                            <Button size="sm" className="h-7 text-xs bg-indigo-600 hover:bg-indigo-700">
                                <MessageCircle className="h-3.5 w-3.5 mr-1.5" />
                                Abrir Chat
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
                            Datos de Contacto
                        </h3>
                        <div className="space-y-3">
                            <div className="flex items-center gap-2 text-gray-700">
                                <Phone className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">{lead.phone}</span>
                            </div>
                            <div className="flex items-center gap-2 text-gray-700">
                                <Mail className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">{lead.email || "Sin email"}</span>
                            </div>
                            <div className="flex items-center gap-2 text-gray-700">
                                <User className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">
                                    Cliente: {lead.campaign?.client?.name || lead.client?.name || "Sin asignar"}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Technical Metadata */}
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <h3 className="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-100">
                            Metadatos del Sistema
                        </h3>
                        <div className="text-xs space-y-2">
                            <div className="flex justify-between items-center">
                                <span className="text-gray-500">Campaña</span>
                                {lead.campaign?.id ? (
                                    <Link
                                        href={route("campaigns.index", { highlight: lead.campaign.id })}
                                        className="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-medium"
                                    >
                                        {lead.campaign?.name || "Desconocida"}
                                        <ExternalLink className="h-3 w-3" />
                                    </Link>
                                ) : (
                                    <span className="text-gray-800">N/A</span>
                                )}
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">ID del Lead</span>
                                <span className="font-mono text-gray-800">
                                    {lead.id?.substring(0, 8) || lead.id}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Fuente</span>
                                <span className="font-medium text-gray-800">
                                    {lead.source_label || lead.source || "Desconocida"}
                                </span>
                            </div>
                            {(lead.contact_source_name || lead.contact_source_phone) && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Contactado vía</span>
                                    <div className="text-right">
                                        {lead.contact_source_name && (
                                            <span className="font-medium text-gray-800 block">
                                                {lead.contact_source_name}
                                            </span>
                                        )}
                                        {lead.contact_source_phone && (
                                            <span className="text-gray-500 text-[10px] font-mono">
                                                {lead.contact_source_phone}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}
                            {lead.option_selected && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Selección IVR</span>
                                    <span className="font-mono text-gray-800">Opción {lead.option_selected}</span>
                                </div>
                            )}
                            {lead.intention && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Intención</span>
                                    <span className={`font-medium ${lead.intention === "interested" ? "text-green-600" : "text-red-600"}`}>
                                        {lead.intention === "interested" ? "Interesado" : "No Interesado"}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Outcome & Decision */}
                    <div className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <h3 className="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-100">
                            Resultado y Decisión
                        </h3>
                        <form onSubmit={handleSaveDecision} className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">
                                    Clasificación Manual
                                </label>
                                <Select
                                    value={data.manual_classification}
                                    onValueChange={(value) => setData("manual_classification", value)}
                                >
                                    <SelectTrigger className="h-8 text-xs border-gray-300">
                                        <SelectValue placeholder="Seleccionar estado..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="interested">Interesado</SelectItem>
                                        <SelectItem value="not_interested">No Interesado</SelectItem>
                                        <SelectItem value="callback">Solicita Callback</SelectItem>
                                        <SelectItem value="qualified">Calificado</SelectItem>
                                        <SelectItem value="disqualified">Descalificado</SelectItem>
                                        <SelectItem value="no_answer">Sin Respuesta</SelectItem>
                                        <SelectItem value="wrong_number">Número Incorrecto</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <textarea
                                className="w-full border border-gray-300 rounded-md shadow-sm text-xs p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Agregar notas sobre la decisión..."
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
                                Guardar Decisión
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Right Column: Timeline / History */}
                <div className="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col h-full overflow-hidden">
                    <div className="px-4 py-3 border-b border-gray-100">
                        <h3 className="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Historial de Actividad
                        </h3>
                    </div>

                    <ScrollArea className="flex-1 px-4 py-3">
                        {timeline.length === 0 ? (
                            <div className="text-center text-gray-400 py-10 text-sm">
                                Sin actividad registrada.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {timeline.map((item, index) => (
                                    <div key={item.id} className="flex gap-3">
                                        <div className="flex flex-col items-center">
                                            <div
                                                className={`w-7 h-7 rounded-full flex items-center justify-center z-10 ${
                                                    item.type === "system"
                                                        ? item.event === "sales_ready"
                                                            ? "bg-emerald-100 text-emerald-600"
                                                            : item.event === "llm_decision"
                                                            ? "bg-violet-100 text-violet-600"
                                                            : "bg-blue-100 text-blue-600"
                                                        : item.type === "call"
                                                        ? "bg-purple-100 text-purple-600"
                                                        : item.sender === "AI Agent"
                                                        ? "bg-green-100 text-green-600"
                                                        : "bg-gray-200 text-gray-600"
                                                }`}
                                            >
                                                {item.type === "system" ? (
                                                    item.icon === "user_plus" ? <UserPlus className="h-3.5 w-3.5" /> :
                                                    item.icon === "rocket" ? <Rocket className="h-3.5 w-3.5" /> :
                                                    item.icon === "brain" ? <Brain className="h-3.5 w-3.5" /> :
                                                    item.icon === "sparkles" ? <Sparkles className="h-3.5 w-3.5" /> :
                                                    <Clock className="h-3.5 w-3.5" />
                                                ) : item.type === "call" ? (
                                                    <Phone className="h-3.5 w-3.5" />
                                                ) : item.sender === "AI Agent" ? (
                                                    <Bot className="h-3.5 w-3.5" />
                                                ) : (
                                                    <User className="h-3.5 w-3.5" />
                                                )}
                                            </div>
                                            {index < timeline.length - 1 && (
                                                <div className="w-0.5 flex-1 bg-gray-100 -mt-1" />
                                            )}
                                        </div>
                                        <div className="flex-1 pb-4">
                                            <div className={`rounded-lg p-3 border ${
                                                item.type === "system" 
                                                    ? "bg-blue-50/50 border-blue-100" 
                                                    : "bg-gray-50 border-gray-100"
                                            }`}>
                                                <div className="flex justify-between items-start mb-1.5">
                                                    <span className="font-semibold text-xs text-gray-900">
                                                        {item.type === "system"
                                                            ? item.event === "lead_created" ? "Lead Creado"
                                                            : item.event === "campaign_executed" ? "Campaña Ejecutada"
                                                            : item.event === "llm_decision" ? "Decisión IA"
                                                            : item.event === "sales_ready" ? "Listo para Ventas"
                                                            : "Evento del Sistema"
                                                        : item.type === "call"
                                                            ? "Llamada Saliente"
                                                            : item.sender}
                                                        {item.type === "message" && item.sender === "AI Agent" && item.sourcePhone && (
                                                            <span className="text-gray-400 font-normal ml-1">
                                                                ({item.sourcePhone})
                                                            </span>
                                                        )}
                                                    </span>
                                                    <span className="text-[10px] text-gray-400">
                                                        {formatDateTime(item.created_at)}
                                                    </span>
                                                </div>

                                                {item.type === "system" ? (
                                                    <p className="text-xs text-gray-600">
                                                        {item.content}
                                                    </p>
                                                ) : item.type === "call" ? (
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
                                                                Escuchar Grabación
                                                            </a>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div>
                                                        {item.direction === "outbound" && (
                                                            <div className="flex items-center gap-1 text-[10px] text-gray-400 mb-1">
                                                                <Send className="h-2.5 w-2.5" />
                                                                Enviado vía {item.channel?.toUpperCase() || "WhatsApp"}
                                                                {lead.contact_source_phone && (
                                                                    <span className="font-mono ml-1">({lead.contact_source_phone})</span>
                                                                )}
                                                            </div>
                                                        )}
                                                        <p className="text-xs text-gray-700 whitespace-pre-wrap">
                                                            {item.content}
                                                        </p>
                                                    </div>
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

