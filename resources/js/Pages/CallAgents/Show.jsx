import CustomFunctionModal from "@/Components/CallAgent/CustomFunctionModal";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, router, useForm } from "@inertiajs/react";
import {
    AlertCircle,
    ArrowLeft,
    ChevronLeft,
    Database,
    MessageSquare,
    Phone,
    Save,
    Settings,
    Shield,
    TestTube,
    Webhook,
    Zap,
} from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import CallSettingsTab from "./Partials/CallSettingsTab";
import FunctionsTab from "./Partials/FunctionsTab";
import PromptBasicTab from "./Partials/PromptBasicTab";
import SpeechSettingsTab from "./Partials/SpeechSettingsTab";
import WebhookSettingsTab from "./Partials/WebhookSettingsTab";

export default function CallAgentShow({
    agent,
    phoneNumbers = [],
    error,
    errorType,
    defaultConfig,
}) {
    const isEditing = !!agent;
    const [activeTestTab, setActiveTestTab] = useState("chat");
    const [configOpen, setConfigOpen] = useState(true);
    const [testOpen, setTestOpen] = useState(true);
    const [customFunctionModalOpen, setCustomFunctionModalOpen] =
        useState(false);
    const [editingFunctionIndex, setEditingFunctionIndex] = useState(null);

    // Combinar configuración base con datos del agente
    const baseConfig = defaultConfig || {};
    const agentConfig = agent || {};

    const { data, setData, post, put, processing, errors } = useForm({
        agent_name: agentConfig.agent_name || "",
        voice_id: agentConfig.voice_id || "",
        language: agentConfig.language || baseConfig.language || "es-ES",
        prompt: agentConfig.prompt || baseConfig.prompt || "",
        first_message:
            agentConfig.begin_message || baseConfig.begin_message || "",
        welcome_message_mode: agentConfig.begin_message
            ? "ai_speaks_first"
            : "user_speaks_first",

        // LLM Settings (de base config con override)
        llm_model:
            agentConfig.retell_llm?.model || baseConfig.llm_model || "gpt-4.1",
        llm_temperature:
            agentConfig.retell_llm?.model_temperature ??
            baseConfig.llm_temperature ??
            0.7,

        // Voice Settings
        voice_speed: agentConfig.voice_speed ?? baseConfig.voice_speed ?? 1.0,
        voice_temperature:
            agentConfig.voice_temperature ??
            baseConfig.voice_temperature ??
            0.7,

        // Realtime Transcription (de base config)
        stt_mode: agentConfig.stt_mode || baseConfig.stt_mode || "fast",
        vocab_specialization:
            agentConfig.vocab_specialization ||
            baseConfig.vocab_specialization ||
            "general",
        denoising_mode:
            agentConfig.denoising_mode ||
            baseConfig.denoising_mode ||
            "noise-cancellation",

        // Call Settings (de base config)
        end_call_after_silence_ms:
            agentConfig.end_call_after_silence_ms ??
            baseConfig.end_call_after_silence_ms ??
            600000,
        max_call_duration_ms:
            agentConfig.max_call_duration_ms ??
            baseConfig.max_call_duration_ms ??
            3600000,
        ring_duration_ms:
            agentConfig.ring_duration_ms ??
            baseConfig.ring_duration_ms ??
            30000,

        // Webhook
        webhook_url: agentConfig.webhook_url || baseConfig.webhook_url || "",
        webhook_timeout_ms:
            agentConfig.webhook_timeout_ms ??
            baseConfig.webhook_timeout_ms ??
            10000,

        // Functions (solo end_call por ahora)
        functions: agentConfig.functions ||
            baseConfig.functions || [{ type: "end_call", name: "end_call" }],
        custom_functions:
            agentConfig.custom_functions || baseConfig.custom_functions || [],
        end_call_enabled: (() => {
            const functions =
                agentConfig.functions || baseConfig.functions || [];
            return functions.some(
                (f) => f.type === "end_call" || f.name === "end_call"
            );
        })(),
    });

    const handleAddCustomFunction = () => {
        setEditingFunctionIndex(null);
        setCustomFunctionModalOpen(true);
    };

    const handleEditCustomFunction = (index) => {
        setEditingFunctionIndex(index);
        setCustomFunctionModalOpen(true);
    };

    const handleSaveCustomFunction = (functionData) => {
        const currentFunctions = [...(data.custom_functions || [])];
        if (editingFunctionIndex !== null) {
            currentFunctions[editingFunctionIndex] = functionData;
        } else {
            currentFunctions.push(functionData);
        }
        setData("custom_functions", currentFunctions);
    };

    const handleDeleteCustomFunction = (index) => {
        const currentFunctions = [...(data.custom_functions || [])];
        currentFunctions.splice(index, 1);
        setData("custom_functions", currentFunctions);
    };

    // Debug: Log agent data cuando se carga o cambia
    useEffect(() => {
        if (agent) {
            console.log("=== AGENT DATA DEBUG ===");
            console.log("Agent completo:", agent);
            console.log("Agent config:", agentConfig);
            console.log("Base config:", baseConfig);
            console.log("Form data:", data);
            console.log("Welcome message mode:", data.welcome_message_mode);
            console.log("First message:", data.first_message);
            console.log("Prompt:", data.prompt);
            console.log("========================");
        }
    }, [agent, data]);

    const handleSubmit = (e) => {
        e.preventDefault();

        // Preparar datos para envío
        const submitData = { ...data };

        // Si welcome_message_mode es "user_speaks_first", asegurar que first_message esté vacío
        if (submitData.welcome_message_mode === "user_speaks_first") {
            submitData.first_message = "";
        }

        console.log("=== SUBMIT DATA ===");
        console.log("Datos a enviar:", submitData);
        console.log("===================");

        if (isEditing) {
            put(route("call-agents.update", agent.agent_id || agent.id), {
                data: submitData,
                onSuccess: () => {
                    toast.success("Agente actualizado exitosamente");
                },
                onError: () => {
                    toast.error("Error al actualizar el agente");
                },
            });
        } else {
            post(route("call-agents.store"), {
                data: submitData,
                onSuccess: () => {
                    toast.success("Agente creado exitosamente");
                    router.visit(route("call-agents.index"));
                },
                onError: () => {
                    toast.error("Error al crear el agente");
                },
            });
        }
    };

    return (
        <AppLayout
            stretch={true}
            header={{
                title: isEditing ? agent.agent_name : "Nuevo Agente",
                subtitle: isEditing ? agent.agent_id : undefined,
                backButton: {
                    href: route("call-agents.index"),
                    variant: "ghost",
                },
                badges: isEditing
                    ? [
                          {
                              label: data.llm_model,
                              className: "bg-muted text-muted-foreground",
                          },
                          {
                              label: data.voice_id || "Sin voz",
                              className: "bg-muted text-muted-foreground",
                          },
                          {
                              label: data.language,
                              className: "bg-muted text-muted-foreground",
                          },
                      ]
                    : undefined,
                actions: (
                    <Button
                        onClick={handleSubmit}
                        size="sm"
                        className="h-8 text-xs px-2"
                        disabled={processing}
                    >
                        <Save className="h-3.5 w-3.5 mr-1.5" />
                        {processing ? "Guardando..." : "Guardar"}
                    </Button>
                ),
            }}
        >
            <Head
                title={
                    isEditing
                        ? `Editar Agente: ${agent.agent_name}`
                        : "Crear Nuevo Agente"
                }
            />

            <div className="flex h-[calc(100vh-3.5rem)] overflow-hidden">
                {/* Error Message */}
                {error && (
                    <div className="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-4">
                        <Card
                            className={
                                errorType === "configuration"
                                    ? "border-amber-200 bg-amber-50"
                                    : "border-red-200 bg-red-50"
                            }
                        >
                            <CardContent className="pt-4 pb-4">
                                <div className="flex items-start gap-2">
                                    <AlertCircle
                                        className={`h-5 w-5 mt-0.5 ${
                                            errorType === "configuration"
                                                ? "text-amber-600"
                                                : "text-red-600"
                                        }`}
                                    />
                                    <div className="flex-1">
                                        <p
                                            className={`font-medium text-sm ${
                                                errorType === "configuration"
                                                    ? "text-amber-800"
                                                    : "text-red-800"
                                            }`}
                                        >
                                            {errorType === "configuration"
                                                ? "Configuración requerida"
                                                : "Error"}
                                        </p>
                                        <p
                                            className={`text-xs mt-1 ${
                                                errorType === "configuration"
                                                    ? "text-amber-600"
                                                    : "text-red-600"
                                            }`}
                                        >
                                            {error}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Main Content - Prompt Editor */}
                <div className="flex-1 flex flex-col overflow-hidden min-w-0 border-r">
                    <div className="border-b flex items-center justify-between bg-background flex-shrink-0 px-2 py-1.5">
                        <div className="flex items-center gap-2 min-w-0">
                            <Link href={route("call-agents.index")}>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7 flex-shrink-0"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                </Button>
                            </Link>
                            <div className="min-w-0">
                                <h1 className="text-base font-semibold truncate">
                                    {isEditing
                                        ? agent.agent_name
                                        : "Nuevo Agente"}
                                </h1>
                                {isEditing && (
                                    <p className="text-xs text-muted-foreground truncate">
                                        {agent.agent_id}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-1.5 flex-shrink-0">
                            <Badge
                                variant="outline"
                                className="text-xs px-1.5 py-0.5"
                            >
                                {data.llm_model}
                            </Badge>
                            <Badge
                                variant="outline"
                                className="text-xs px-1.5 py-0.5"
                            >
                                {data.voice_id || "Sin voz"}
                            </Badge>
                            <Badge
                                variant="outline"
                                className="text-xs px-1.5 py-0.5"
                            >
                                {data.language}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto">
                        <div className="max-w-4xl mx-auto p-3">
                            <div className="space-y-3">
                                <div>
                                    <h2 className="text-xs font-semibold mb-2 flex items-center gap-1.5">
                                        <MessageSquare className="h-3.5 w-3.5" />
                                        Prompt Universal
                                    </h2>
                                    <Textarea
                                        id="prompt_main"
                                        value={data.prompt}
                                        onChange={(e) =>
                                            setData("prompt", e.target.value)
                                        }
                                        rows={20}
                                        className="font-mono text-sm resize-none min-h-[400px]"
                                        placeholder="Type in a universal prompt for your agent, such as its role, conversational style, objective, etc."
                                    />
                                </div>

                                {data.welcome_message_mode ===
                                    "ai_speaks_first" && (
                                    <div className="space-y-1.5">
                                        <Label
                                            htmlFor="first_message_text"
                                            className="text-xs font-medium"
                                        >
                                            Mensaje de Bienvenida
                                        </Label>
                                        <Textarea
                                            id="first_message_text"
                                            value={data.first_message || ""}
                                            onChange={(e) =>
                                                setData(
                                                    "first_message",
                                                    e.target.value
                                                )
                                            }
                                            rows={4}
                                            className="text-sm resize-none"
                                            placeholder="Escribe el mensaje que dirá el agente al iniciar la llamada..."
                                        />
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Configuration Sidebar */}
                {configOpen ? (
                    <div className="w-72 border-r bg-muted/30 flex flex-col flex-shrink-0">
                        <div className="flex items-center justify-between border-b bg-background p-1.5 flex-shrink-0">
                            <div className="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="h-6 w-6"
                                    onClick={() => setConfigOpen(false)}
                                >
                                    <ChevronLeft className="h-3.5 w-3.5" />
                                </Button>
                                <h2 className="text-xs font-semibold flex items-center gap-1.5">
                                    <Settings className="h-3.5 w-3.5" />
                                    Configuración
                                </h2>
                            </div>
                            <Button
                                onClick={handleSubmit}
                                size="sm"
                                className="h-7 text-xs px-2"
                                disabled={processing}
                            >
                                <Save className="h-3 w-3 mr-1" />
                                {processing ? "..." : "Guardar"}
                            </Button>
                        </div>

                        <div className="flex-1 overflow-y-auto">
                            <form onSubmit={handleSubmit} className="p-1.5">
                                <Accordion
                                    type="multiple"
                                    defaultValue={["prompt", "functions"]}
                                    className="w-full"
                                >
                                    {/* Prompt & Basic Info */}
                                    <AccordionItem
                                        value="prompt"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <MessageSquare className="h-3.5 w-3.5" />
                                                Prompt & Básico
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <PromptBasicTab
                                                data={data}
                                                setData={setData}
                                                errors={errors}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Functions */}
                                    <AccordionItem
                                        value="functions"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Zap className="h-3.5 w-3.5" />
                                                Funciones
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <FunctionsTab
                                                data={data}
                                                setData={setData}
                                                onAddCustomFunction={
                                                    handleAddCustomFunction
                                                }
                                                onEditCustomFunction={
                                                    handleEditCustomFunction
                                                }
                                                onDeleteCustomFunction={
                                                    handleDeleteCustomFunction
                                                }
                                            />
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Knowledge Base */}
                                    <AccordionItem
                                        value="knowledge"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Database className="h-3.5 w-3.5" />
                                                Knowledge Base
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <p className="text-xs text-muted-foreground">
                                                Coming Soon
                                            </p>
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Speech Settings */}
                                    <AccordionItem
                                        value="speech"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Phone className="h-3.5 w-3.5" />
                                                Speech Settings
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <SpeechSettingsTab
                                                data={data}
                                                setData={setData}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Call Settings */}
                                    <AccordionItem
                                        value="call"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Phone className="h-3.5 w-3.5" />
                                                Call Settings
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <CallSettingsTab
                                                data={data}
                                                setData={setData}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Post-Call Data */}
                                    <AccordionItem
                                        value="postcall"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Database className="h-3.5 w-3.5" />
                                                Post-Call Data
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <p className="text-xs text-muted-foreground">
                                                Coming Soon
                                            </p>
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Security */}
                                    <AccordionItem
                                        value="security"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Shield className="h-3.5 w-3.5" />
                                                Security & Fallback
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <p className="text-xs text-muted-foreground">
                                                Coming Soon
                                            </p>
                                        </AccordionContent>
                                    </AccordionItem>

                                    {/* Webhook Settings */}
                                    <AccordionItem
                                        value="webhook"
                                        className="border-none"
                                    >
                                        <AccordionTrigger className="text-xs font-medium py-2 hover:no-underline">
                                            <div className="flex items-center gap-2">
                                                <Webhook className="h-3.5 w-3.5" />
                                                Webhook
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pb-2">
                                            <WebhookSettingsTab
                                                data={data}
                                                setData={setData}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </form>
                        </div>
                    </div>
                ) : (
                    <div className="border-r bg-muted/30 flex-shrink-0 flex flex-col items-center p-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7"
                            onClick={() => setConfigOpen(true)}
                            title="Mostrar Configuración"
                        >
                            <Settings className="h-4 w-4" />
                        </Button>
                    </div>
                )}

                {/* Test Sidebar */}
                {testOpen ? (
                    <div className="w-72 border-l bg-muted/30 flex flex-col flex-shrink-0">
                        <div className="flex items-center justify-between border-b bg-background p-1.5 flex-shrink-0">
                            <h2 className="text-xs font-semibold flex items-center gap-1.5">
                                <TestTube className="h-3.5 w-3.5" />
                                Test
                            </h2>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6"
                                onClick={() => setTestOpen(false)}
                            >
                                <ChevronLeft className="h-3.5 w-3.5" />
                            </Button>
                        </div>

                        <div className="flex-1 overflow-y-auto">
                            <div className="p-2 space-y-2">
                                <div className="flex gap-1 border-b">
                                    <button
                                        onClick={() => setActiveTestTab("chat")}
                                        className={`px-2 py-1 text-xs font-medium border-b-2 transition-colors ${
                                            activeTestTab === "chat"
                                                ? "border-primary text-primary"
                                                : "border-transparent text-muted-foreground"
                                        }`}
                                    >
                                        Test Chat
                                    </button>
                                    <button
                                        onClick={() =>
                                            setActiveTestTab("audio")
                                        }
                                        className={`px-2 py-1 text-xs font-medium border-b-2 transition-colors ${
                                            activeTestTab === "audio"
                                                ? "border-primary text-primary"
                                                : "border-transparent text-muted-foreground"
                                        }`}
                                    >
                                        Test Audio
                                    </button>
                                </div>

                                {activeTestTab === "chat" && (
                                    <div className="space-y-2">
                                        <div className="h-64 border rounded-lg p-3 bg-background">
                                            <div className="flex flex-col items-center justify-center h-full text-center text-sm text-muted-foreground">
                                                <MessageSquare className="h-8 w-8 mb-2 opacity-50" />
                                                <p>Test Chat</p>
                                                <p className="text-xs mt-1">
                                                    Coming Soon
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {activeTestTab === "audio" && (
                                    <div className="space-y-2">
                                        <div className="h-64 border rounded-lg p-3 bg-background">
                                            <div className="flex flex-col items-center justify-center h-full text-center text-sm text-muted-foreground">
                                                <Phone className="h-8 w-8 mb-2 opacity-50" />
                                                <p>Test Audio</p>
                                                <p className="text-xs mt-1">
                                                    Coming Soon
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="border-l bg-muted/30 flex-shrink-0 flex flex-col items-center p-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7"
                            onClick={() => setTestOpen(true)}
                            title="Mostrar Test"
                        >
                            <TestTube className="h-4 w-4" />
                        </Button>
                    </div>
                )}
            </div>

            {/* Custom Function Modal */}
            <CustomFunctionModal
                open={customFunctionModalOpen}
                onOpenChange={setCustomFunctionModalOpen}
                function={
                    editingFunctionIndex !== null
                        ? data.custom_functions?.[editingFunctionIndex]
                        : null
                }
                onSave={handleSaveCustomFunction}
            />
        </AppLayout>
    );
}
