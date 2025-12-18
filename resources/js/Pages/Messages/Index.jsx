import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { ScrollArea } from "@/Components/ui/scroll-area";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, router } from "@inertiajs/react";
import {
    Bot,
    Building2,
    Circle,
    MessageSquare,
    MoreVertical,
    Pause,
    Phone,
    Search,
    Send,
    Target,
    User,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";

export default function MessagesIndex({
    conversations,
    selectedConversation,
    messages: initialMessages,
    filters,
}) {
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [messageInput, setMessageInput] = useState("");
    const [messages, setMessages] = useState(initialMessages || []);
    const [sending, setSending] = useState(false);
    const [aiActive, setAiActive] = useState(selectedConversation?.ai_active ?? true);
    const messagesEndRef = useRef(null);

    // Scroll to bottom when messages change
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    // Update messages when selectedConversation changes
    useEffect(() => {
        setMessages(initialMessages || []);
        setAiActive(selectedConversation?.ai_active ?? true);
    }, [initialMessages, selectedConversation]);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route("messages.index"), { search: searchQuery }, { preserveState: true });
    };

    const selectConversation = (leadId) => {
        router.get(
            route("messages.index"),
            { lead_id: leadId, search: searchQuery },
            { preserveState: true }
        );
    };

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!messageInput.trim() || !selectedConversation || sending) return;

        setSending(true);
        const content = messageInput;
        setMessageInput("");

        // Optimistic update
        const tempMessage = {
            id: `temp-${Date.now()}`,
            content,
            direction: "outbound",
            is_from_lead: false,
            created_at: new Date().toISOString(),
        };
        setMessages((prev) => [...prev, tempMessage]);

        try {
            const response = await fetch(
                route("messages.send", selectedConversation.id),
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify({ content }),
                }
            );

            if (response.ok) {
                const data = await response.json();
                // Replace temp message with real one
                setMessages((prev) =>
                    prev.map((msg) =>
                        msg.id === tempMessage.id ? data.message : msg
                    )
                );
            }
        } catch (error) {
            console.error("Error sending message:", error);
            // Remove temp message on error
            setMessages((prev) => prev.filter((msg) => msg.id !== tempMessage.id));
            setMessageInput(content);
        } finally {
            setSending(false);
        }
    };

    const toggleAiAgent = async () => {
        if (!selectedConversation) return;

        try {
            const response = await fetch(
                route("messages.toggle-ai", selectedConversation.id),
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                }
            );

            if (response.ok) {
                const data = await response.json();
                setAiActive(data.ai_active);
            }
        } catch (error) {
            console.error("Error toggling AI:", error);
        }
    };

    const formatTime = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toLocaleTimeString("es-ES", {
            hour: "2-digit",
            minute: "2-digit",
        }) + " a. m.";
    };

    const getSourceColor = (source) => {
        const colors = {
            IVR: "bg-green-500",
            WEBHOOK: "bg-green-500",
            CSV: "bg-green-500",
            MANUAL: "bg-green-500",
            WHATSAPP: "bg-orange-500",
        };
        return colors[source] || "bg-gray-500";
    };

    const getStatusBadge = (conv) => {
        // Priority: intention_status > automation_status > status
        if (conv.intention_status === "finalized") {
            return {
                label: "Listo",
                className: "bg-emerald-100 text-emerald-700 border-emerald-200",
            };
        }
        if (conv.intention_status === "pending") {
            return {
                label: "Esperando",
                className: "bg-amber-100 text-amber-700 border-amber-200",
            };
        }
        if (conv.automation_status === "processing") {
            return {
                label: "Procesando",
                className: "bg-blue-100 text-blue-700 border-blue-200",
            };
        }
        if (conv.status === "new" || conv.status === "pending") {
            return {
                label: "Nuevo",
                className: "bg-indigo-100 text-indigo-700 border-indigo-200",
            };
        }
        if (conv.status === "in_progress") {
            return {
                label: "En curso",
                className: "bg-blue-100 text-blue-700 border-blue-200",
            };
        }
        return {
            label: conv.status_label || conv.status || "—",
            className: "bg-gray-100 text-gray-600 border-gray-200",
        };
    };

    return (
        <AppLayout>
            <Head title="Mensajes" />

            {/* Main Container - same structure as Campaigns */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm h-[calc(100vh-7rem)] flex overflow-hidden">
                {/* Conversations List - Left Panel */}
                <div className="w-96 border-r flex flex-col bg-white flex-shrink-0">
                    {/* Search - Fixed height */}
                    <div className="p-3 border-b flex-shrink-0">
                        <form onSubmit={handleSearch}>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Buscar conversaciones..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-9 bg-gray-100 border-0 h-9 text-sm"
                                />
                            </div>
                        </form>
                    </div>

                    {/* Conversations List - Scrollable */}
                    <ScrollArea className="flex-1">
                        {conversations.data?.length === 0 ? (
                            <div className="p-4 text-center text-gray-500">
                                <MessageSquare className="h-12 w-12 mx-auto mb-2 opacity-20" />
                                <p className="text-sm">Sin conversaciones</p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {conversations.data?.map((conv) => {
                                    const statusBadge = getStatusBadge(conv);
                                    return (
                                        <div
                                            key={conv.id}
                                            onClick={() => selectConversation(conv.id)}
                                            className={`p-3 cursor-pointer hover:bg-gray-50 transition-colors ${
                                                selectedConversation?.id === conv.id
                                                    ? "bg-indigo-50 border-l-2 border-l-indigo-600"
                                                    : ""
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1 min-w-0">
                                                    {/* Name & Status */}
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-sm text-gray-900 truncate">
                                                            {conv.name}
                                                        </span>
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[9px] px-1.5 py-0 h-4 font-medium ${statusBadge.className}`}
                                                        >
                                                            {statusBadge.label}
                                                        </Badge>
                                                    </div>

                                                    {/* Phone */}
                                                    <p className="text-[11px] text-gray-500 mt-0.5 font-mono">
                                                        {conv.phone}
                                                    </p>

                                                    {/* Last message */}
                                                    <p className="text-xs text-gray-500 truncate mt-1">
                                                        {conv.last_message || "Sin mensajes"}
                                                    </p>

                                                    {/* Source + Campaign + Client */}
                                                    <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                                                        <div className="flex items-center gap-1">
                                                            <Circle
                                                                className={`h-2 w-2 fill-current ${getSourceColor(
                                                                    conv.source_label
                                                                )} text-transparent`}
                                                            />
                                                            <span className="text-[10px] text-gray-400 uppercase">
                                                                {conv.source_label}
                                                            </span>
                                                        </div>
                                                        {conv.campaign && (
                                                            <div className="flex items-center gap-1 text-[10px] text-gray-400">
                                                                <Target className="h-2.5 w-2.5" />
                                                                <span className="truncate max-w-[100px]">
                                                                    {conv.campaign.name}
                                                                </span>
                                                            </div>
                                                        )}
                                                        {conv.client && (
                                                            <div className="flex items-center gap-1 text-[10px] text-purple-500">
                                                                <Building2 className="h-2.5 w-2.5" />
                                                                <span className="truncate max-w-[80px]">
                                                                    {conv.client.name}
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                <span className="text-[10px] text-gray-400 whitespace-nowrap">
                                                    {formatTime(conv.last_message_time)}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </ScrollArea>
                </div>

                    {/* Chat Panel - Right */}
                    <div className="flex-1 flex flex-col min-w-0">
                        {selectedConversation ? (
                            <>
                                {/* Chat Header - Fixed height */}
                                <div className="border-b flex items-center justify-between px-4 py-3 bg-white flex-shrink-0">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <User className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <h2 className="font-medium text-sm text-gray-900 truncate">
                                                    {selectedConversation.name}
                                                </h2>
                                                <Badge
                                                    variant="outline"
                                                    className={`text-[9px] px-1.5 py-0 h-4 font-medium ${
                                                        getStatusBadge(selectedConversation).className
                                                    }`}
                                                >
                                                    {getStatusBadge(selectedConversation).label}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-gray-500 font-mono">
                                                {selectedConversation.phone}
                                            </p>
                                            <div className="flex items-center gap-2 mt-0.5">
                                                {selectedConversation.campaign && (
                                                    <Link
                                                        href={route("campaigns.show", selectedConversation.campaign.id)}
                                                        className="flex items-center gap-1 text-[10px] text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        <Target className="h-2.5 w-2.5" />
                                                        {selectedConversation.campaign.name}
                                                    </Link>
                                                )}
                                                {selectedConversation.client && (
                                                    <span className="flex items-center gap-1 text-[10px] text-purple-600">
                                                        <Building2 className="h-2.5 w-2.5" />
                                                        {selectedConversation.client.name}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        <Link
                                            href={route("leads.show", selectedConversation.id)}
                                            className="text-xs text-indigo-600 hover:text-indigo-800 underline mr-2"
                                        >
                                            Ver Lead
                                        </Link>

                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                            <Phone className="h-4 w-4 text-gray-500" />
                                        </Button>

                                        <Badge
                                            className={`${
                                                aiActive
                                                    ? "bg-green-50 text-green-700 border-green-200"
                                                    : "bg-gray-100 text-gray-600 border-gray-200"
                                            } flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-medium border`}
                                        >
                                            <Bot className="h-3 w-3" />
                                            {aiActive ? "IA ACTIVA" : "IA PAUSADA"}
                                        </Badge>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={toggleAiAgent}
                                            title={aiActive ? "Pausar IA" : "Activar IA"}
                                            className="h-8 w-8"
                                        >
                                            <Pause className="h-4 w-4 text-gray-500" />
                                        </Button>

                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                            <MoreVertical className="h-4 w-4 text-gray-500" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Messages Area - Scrollable */}
                                <ScrollArea className="flex-1 bg-gray-50">
                                    <div className="p-3 space-y-2.5 max-w-3xl mx-auto">
                                        {messages.length === 0 ? (
                                            <div className="text-center py-12 text-gray-500">
                                                <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-20" />
                                                <p className="text-sm">Sin mensajes</p>
                                                <p className="text-xs mt-1">
                                                    Inicia la conversación o espera la respuesta del lead
                                                </p>
                                            </div>
                                        ) : (
                                            messages.map((message) => (
                                                <div
                                                    key={message.id}
                                                    className={`flex ${
                                                        message.is_from_lead
                                                            ? "justify-start"
                                                            : "justify-end"
                                                    }`}
                                                >
                                                    <div
                                                        className={`max-w-[70%] rounded-2xl px-3.5 py-2 ${
                                                            message.is_from_lead
                                                                ? "bg-white border border-gray-200 shadow-sm"
                                                                : "bg-green-100"
                                                        }`}
                                                    >
                                                        {!message.is_from_lead && (
                                                            <p className="text-[9px] font-semibold text-green-700 mb-0.5 uppercase tracking-wide">
                                                                BOT IA
                                                            </p>
                                                        )}
                                                        <p className="text-sm text-gray-900 whitespace-pre-wrap">
                                                            {message.content}
                                                        </p>
                                                        <p
                                                            className={`text-[10px] mt-1 ${
                                                                message.is_from_lead
                                                                    ? "text-gray-400"
                                                                    : "text-green-600"
                                                            }`}
                                                        >
                                                            {formatTime(message.created_at)}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                        <div ref={messagesEndRef} />
                                    </div>
                                </ScrollArea>

                                {/* Message Input - Fixed height */}
                                <div className="p-3 border-t bg-white flex-shrink-0">
                                    <form onSubmit={handleSendMessage}>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                placeholder={
                                                    aiActive
                                                        ? "Pausa la IA para escribir..."
                                                        : "Escribe un mensaje..."
                                                }
                                                value={messageInput}
                                                onChange={(e) => setMessageInput(e.target.value)}
                                                disabled={aiActive}
                                                className="flex-1 bg-gray-100 border-0 rounded-full px-4 h-9 text-sm"
                                            />
                                            <Button
                                                type="submit"
                                                size="icon"
                                                disabled={!messageInput.trim() || sending || aiActive}
                                                className="rounded-full bg-green-500 hover:bg-green-600 h-9 w-9 flex-shrink-0"
                                            >
                                                <Send className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </form>
                                    {aiActive && (
                                        <p className="text-[10px] text-gray-500 mt-1.5 text-center">
                                            <span className="font-medium">Nota:</span> Pausa el agente IA
                                            para tomar control de la conversación.
                                        </p>
                                    )}
                                </div>
                            </>
                        ) : (
                            /* No conversation selected */
                            <div className="flex-1 flex items-center justify-center bg-gray-50">
                                <div className="text-center text-gray-500">
                                    <MessageSquare className="h-16 w-16 mx-auto mb-4 opacity-20" />
                                    <h3 className="text-base font-medium text-gray-700">
                                        Selecciona una conversación
                                    </h3>
                                    <p className="text-xs mt-1">
                                        Elige un chat del panel izquierdo para ver los mensajes
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
        </AppLayout>
    );
}
