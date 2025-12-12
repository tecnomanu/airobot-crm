import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ScrollArea } from "@/components/ui/scroll-area";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import {
    Bot,
    Circle,
    MessageSquare,
    MoreVertical,
    Pause,
    Phone,
    Search,
    Send,
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

    return (
        <AppLayout>
            <Head title="Messages" />

            {/* Main Container with consistent padding */}
            <div className="p-4 h-[calc(100vh-3rem)]">
                {/* Card Container - Fixed height, no overflow */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-sm h-full flex overflow-hidden">
                    {/* Conversations List - Left Panel */}
                    <div className="w-80 border-r flex flex-col bg-white flex-shrink-0">
                        {/* Search - Fixed height */}
                        <div className="p-4 border-b flex-shrink-0">
                            <form onSubmit={handleSearch}>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Search chats..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-9 bg-gray-100 border-0 h-10"
                                    />
                                </div>
                            </form>
                        </div>

                        {/* Conversations List - Scrollable */}
                        <ScrollArea className="flex-1">
                            {conversations.data?.length === 0 ? (
                                <div className="p-4 text-center text-gray-500">
                                    <MessageSquare className="h-12 w-12 mx-auto mb-2 opacity-20" />
                                    <p className="text-sm">No conversations yet</p>
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {conversations.data?.map((conv) => (
                                        <div
                                            key={conv.id}
                                            onClick={() => selectConversation(conv.id)}
                                            className={`p-4 cursor-pointer hover:bg-gray-50 transition-colors ${
                                                selectedConversation?.id === conv.id
                                                    ? "bg-indigo-50 border-l-2 border-l-indigo-600"
                                                    : ""
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-sm text-gray-900 truncate">
                                                            {conv.name}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-gray-500 truncate mt-1">
                                                        {conv.last_message || "No messages"}
                                                    </p>
                                                    <div className="flex items-center gap-1.5 mt-1.5">
                                                        <Circle
                                                            className={`h-2 w-2 fill-current ${getSourceColor(
                                                                conv.source_label
                                                            )} text-transparent`}
                                                        />
                                                        <span className="text-[10px] text-gray-400 uppercase tracking-wide">
                                                            {conv.source_label}
                                                        </span>
                                                    </div>
                                                </div>
                                                <span className="text-[10px] text-gray-400 whitespace-nowrap">
                                                    {formatTime(conv.last_message_time)}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </ScrollArea>
                    </div>

                    {/* Chat Panel - Right */}
                    <div className="flex-1 flex flex-col min-w-0">
                        {selectedConversation ? (
                            <>
                                {/* Chat Header - Fixed height */}
                                <div className="h-16 border-b flex items-center justify-between px-4 bg-white flex-shrink-0">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <User className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <div className="min-w-0">
                                            <h2 className="font-medium text-sm text-gray-900 truncate">
                                                {selectedConversation.name}
                                            </h2>
                                            <p className="text-xs text-gray-500">
                                                {selectedConversation.phone}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        <Button variant="ghost" size="icon" className="h-9 w-9">
                                            <Phone className="h-4 w-4 text-gray-500" />
                                        </Button>

                                        <Badge
                                            className={`${
                                                aiActive
                                                    ? "bg-green-50 text-green-700 border-green-200"
                                                    : "bg-gray-100 text-gray-600 border-gray-200"
                                            } flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium border`}
                                        >
                                            <Bot className="h-3.5 w-3.5" />
                                            {aiActive ? "AI DRIVING" : "AI PAUSED"}
                                        </Badge>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={toggleAiAgent}
                                            title={aiActive ? "Pause AI" : "Activate AI"}
                                            className="h-9 w-9"
                                        >
                                            <Pause className="h-4 w-4 text-gray-500" />
                                        </Button>

                                        <Button variant="ghost" size="icon" className="h-9 w-9">
                                            <MoreVertical className="h-4 w-4 text-gray-500" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Messages Area - Scrollable */}
                                <ScrollArea className="flex-1 bg-gray-50">
                                    <div className="p-4 space-y-3 max-w-3xl mx-auto">
                                        {messages.length === 0 ? (
                                            <div className="text-center py-12 text-gray-500">
                                                <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-20" />
                                                <p className="text-sm">No messages yet</p>
                                                <p className="text-xs mt-1">
                                                    Start the conversation or wait for the lead to respond
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
                                                        className={`max-w-[70%] rounded-2xl px-4 py-2.5 ${
                                                            message.is_from_lead
                                                                ? "bg-white border border-gray-200 shadow-sm"
                                                                : "bg-green-100"
                                                        }`}
                                                    >
                                                        {!message.is_from_lead && (
                                                            <p className="text-[10px] font-semibold text-green-700 mb-0.5 uppercase tracking-wide">
                                                                AI BOT
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
                                <div className="p-4 border-t bg-white flex-shrink-0">
                                    <form onSubmit={handleSendMessage}>
                                        <div className="flex items-center gap-3">
                                            <Input
                                                placeholder={
                                                    aiActive
                                                        ? "Pause AI to type manually..."
                                                        : "Type a message..."
                                                }
                                                value={messageInput}
                                                onChange={(e) => setMessageInput(e.target.value)}
                                                disabled={aiActive}
                                                className="flex-1 bg-gray-100 border-0 rounded-full px-4 h-10 text-sm"
                                            />
                                            <Button
                                                type="submit"
                                                size="icon"
                                                disabled={!messageInput.trim() || sending || aiActive}
                                                className="rounded-full bg-green-500 hover:bg-green-600 h-10 w-10 flex-shrink-0"
                                            >
                                                <Send className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </form>
                                    {aiActive && (
                                        <p className="text-[10px] text-gray-500 mt-2 text-center">
                                            <span className="font-medium">Note:</span> Pause the AI Agent
                                            to take over the conversation.
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
                                        Select a conversation
                                    </h3>
                                    <p className="text-xs mt-1">
                                        Choose a chat from the left panel to view messages
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
