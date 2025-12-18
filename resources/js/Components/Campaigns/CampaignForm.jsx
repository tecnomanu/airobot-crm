import AgentsTab from "@/Components/Campaigns/AgentsTab";
import AutomationTab from "@/Components/Campaigns/AutomationTab";
import BasicInfoTab from "@/Components/Campaigns/BasicInfoTab";
import DirectActionTab from "@/Components/Campaigns/DirectActionTab";
import IntentionWebhookTab from "@/Components/Campaigns/IntentionWebhookTab";
import TemplatesTab from "@/Components/Campaigns/TemplatesTab";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import { Head, useForm } from "@inertiajs/react";
import { AlertTriangle, GitBranch, Save, Zap } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";

export default function CampaignForm({
    campaign = {},
    templates = [],
    clients = [],
    whatsapp_sources = [],
    webhook_sources = [],
    mode = "edit" // "create" | "edit"
}) {
    const isCreating = mode === "create";
    const [activeTab, setActiveTab] = useState("basic");

    // Default configuration for new campaigns
    const defaultCampaign = {
        name: "",
        client_id: "",
        description: "",
        status: "active",
        strategy_type: "dynamic", // Default to dynamic but user can change
        auto_process_enabled: true,
        configuration: {},
        ...campaign
    };

    // Determine campaign type (reactive to form data for creation)
    // For edit, it comes from prop. For create, it comes from form data.
    
    // Preparar datos con nueva estructura de modelos relacionados
    const callAgent = defaultCampaign.call_agent || {
        name: "",
        provider: "",
        config: {},
        enabled: true,
    };
    const whatsappAgent = defaultCampaign.whatsapp_agent || {
        name: "",
        source_id: null,
        config: {},
        enabled: true,
    };

    // Direct campaign config from configuration field
    const directConfig = defaultCampaign.configuration || {};

    // Convertir opciones de array a objeto indexado por option_key
    const optionsArray = defaultCampaign.options || [];
    const optionsMap = {};
    optionsArray.forEach((opt) => {
        optionsMap[opt.option_key] = opt;
    });

    const { data, setData, post, put, processing, errors } = useForm({
        name: defaultCampaign.name || "",
        client_id: defaultCampaign.client_id || "",
        description: defaultCampaign.description || "",
        status: defaultCampaign.status || "active",
        slug: defaultCampaign.slug || "",
        strategy_type: defaultCampaign.strategy_type || "dynamic",
        auto_process_enabled:
            defaultCampaign.auto_process_enabled !== undefined
                ? defaultCampaign.auto_process_enabled
                : true,

        // Direct campaign fields
        direct_action: directConfig.trigger_action || "skip",
        direct_source_id: directConfig.source_id || null,
        direct_message: directConfig.message || "",
        direct_template_id: directConfig.template_id || null,

        // Intention Webhooks
        intention_interested_webhook_id:
            defaultCampaign.intention_interested_webhook_id || null,
        intention_not_interested_webhook_id:
            defaultCampaign.intention_not_interested_webhook_id || null,
        send_intention_interested_webhook:
            defaultCampaign.send_intention_interested_webhook || false,
        send_intention_not_interested_webhook:
            defaultCampaign.send_intention_not_interested_webhook || false,

        // Call Agent
        call_agent: {
            name: callAgent.name || "",
            provider: callAgent.provider || "",
            config: callAgent.config || {},
            enabled: callAgent.enabled !== undefined ? callAgent.enabled : true,
        },

        // WhatsApp Agent
        whatsapp_agent: {
            name: whatsappAgent.name || "",
            source_id: whatsappAgent.source_id || null,
            config: whatsappAgent.config || {},
            enabled:
                whatsappAgent.enabled !== undefined
                    ? whatsappAgent.enabled
                    : true,
        },
        
        // Google Sheets Integration
        google_integration_id: defaultCampaign.google_integration_id || null,
        google_spreadsheet_id: defaultCampaign.google_spreadsheet_id || "",
        google_sheet_name: defaultCampaign.google_sheet_name || "",

        // Options (solo para campañas múltiples)
        options: [
            optionsMap["1"] || {
                option_key: "1",
                action: "skip",
                source_id: null,
                template_id: null,
                message: "",
                delay: 5,
                enabled: true,
            },
            optionsMap["2"] || {
                option_key: "2",
                action: "skip",
                source_id: null,
                template_id: null,
                message: "",
                delay: 5,
                enabled: true,
            },
            optionsMap["i"] || {
                option_key: "i",
                action: "skip",
                source_id: null,
                template_id: null,
                message: "",
                delay: 5,
                enabled: true,
            },
            optionsMap["t"] || {
                option_key: "t",
                action: "skip",
                source_id: null,
                template_id: null,
                message: "",
                delay: 5,
                enabled: true,
            },
        ],
    });

    const isDirectCampaign = data.strategy_type === "direct";

    // Obtener la fuente seleccionada actual
    const selectedWhatsappSource = whatsapp_sources?.find(
        (s) => s.id === data.whatsapp_agent.source_id
    );

    // Determinar qué tab contiene errores
    const getTabsWithErrors = () => {
        const tabs = {
            basic: ["name", "client_id", "description", "status"],
            agents: [
                "call_agent",
                "call_agent.name",
                "call_agent.provider",
                "call_agent.config",
                "whatsapp_agent",
                "whatsapp_agent.name",
                "whatsapp_agent.source_id",
                "whatsapp_agent.config",
            ],
            automation: ["options"],
        };

        const tabsWithErrors = {};
        Object.entries(tabs).forEach(([tabKey, fields]) => {
            const hasError = fields.some((field) => errors[field]);
            if (hasError) {
                tabsWithErrors[tabKey] = true;
            }
        });

        return tabsWithErrors;
    };

    const tabsWithErrors = getTabsWithErrors();

    // Mostrar errores en toast cuando hay errores de validación
    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            const firstError = Object.values(errors)[0];
            toast.error(firstError, {
                description: `Se encontraron ${
                    Object.keys(errors).length
                } error(es) de validación`,
                duration: 5000,
            });
        }
    }, [errors]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isCreating) {
            post(route("campaigns.store"), {
                onSuccess: () => {
                    toast.success("Campaña creada exitosamente");
                },
                onError: (errors) => {
                    console.error("Validation errors:", errors);
                },
            });
        } else {
            put(route("campaigns.update", defaultCampaign.id), {
                preserveScroll: true,
                preserveState: true,
                only: ["campaign", "errors"],
                onSuccess: () => {
                    toast.success("Campaña actualizada exitosamente");
                },
                onError: (errors) => {
                    console.error("Validation errors:", errors);
                },
            });
        }
    };

    // Get tabs based on campaign type
    const getTabsConfig = () => {
        if (isDirectCampaign) {
            return [
                { value: "basic", label: "Información Básica", hasError: tabsWithErrors.basic },
                { value: "action", label: "Acción Directa", hasError: tabsWithErrors.agents },
                { value: "webhooks", label: "Webhooks", hasError: false },
            ];
        }
        return [
            { value: "basic", label: "Información Básica", hasError: tabsWithErrors.basic },
            { value: "agents", label: "Agentes", hasError: tabsWithErrors.agents },
            { value: "automation", label: "Automatización", hasError: tabsWithErrors.automation },
            { value: "webhooks", label: "Webhooks", hasError: false },
            { value: "templates", label: "Plantillas", hasError: false },
        ];
    };

    const tabsConfig = getTabsConfig();

    return (
        <AppLayout
            header={{
                title: isCreating ? (data.name || "Nueva Campaña") : defaultCampaign.name,
                subtitle: (
                    <div className="flex items-center gap-2">
                        <span>{isCreating ? "Creando campaña" : "Configuración de campaña"}</span>
                        <Badge
                            variant="outline"
                            className={`text-[10px] ${
                                isDirectCampaign
                                    ? "bg-green-50 text-green-700 border-green-200"
                                    : "bg-blue-50 text-blue-700 border-blue-200"
                            }`}
                        >
                            {isDirectCampaign ? (
                                <><Zap className="h-3 w-3 mr-1" /> Directa</>
                            ) : (
                                <><GitBranch className="h-3 w-3 mr-1" /> Múltiple</>
                            )}
                        </Badge>
                    </div>
                ),
                backButton: {
                    href: route("campaigns.index"),
                    variant: "ghost",
                },
                actions: (
                    <Button
                        onClick={handleSubmit}
                        size="sm"
                        className="h-8 text-xs px-2 shadow-sm"
                        disabled={processing}
                    >
                        <Save className="h-3.5 w-3.5 mr-1.5" />
                        {isCreating ? "Crear Campaña" : "Guardar Cambios"}
                    </Button>
                ),
            }}
        >
            <Head title={isCreating ? "Nueva Campaña" : `Campaña: ${defaultCampaign.name}`} />

            <div className="space-y-6">
                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="w-full justify-start rounded-none border-b bg-transparent p-0 mb-6">
                        {tabsConfig.map((tab) => (
                            <TabsTrigger 
                                key={tab.value} 
                                value={tab.value} 
                                className="relative rounded-none border-b-2 border-transparent bg-transparent px-6 pb-3 pt-2 font-medium text-muted-foreground shadow-none transition-none data-[state=active]:border-indigo-600 data-[state=active]:text-indigo-600 data-[state=active]:shadow-none hover:text-indigo-600/80"
                            >
                                {tab.label}
                                {tab.hasError && (
                                    <AlertTriangle className="ml-2 h-4 w-4 text-amber-500" />
                                )}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {activeTab === "basic" && (
                        <TabsContent value="basic" className="mt-0">
                            <BasicInfoTab
                                data={data}
                                setData={setData}
                                errors={errors}
                                clients={clients}
                                campaign={defaultCampaign}
                                isCreating={isCreating}
                            />
                        </TabsContent>
                    )}

                    {/* Direct Campaign: Action Tab */}
                    {activeTab === "action" && (
                        <TabsContent value="action" className="mt-0">
                            <DirectActionTab
                                data={data}
                                setData={setData}
                                campaign={defaultCampaign}
                                templates={templates}
                                whatsappSources={whatsapp_sources || []}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                                errors={errors}
                            />
                        </TabsContent>
                    )}

                    {/* Agents Tab - Only for Multiple Campaign */}
                    {activeTab === "agents" && (
                        <TabsContent value="agents" className="mt-0">
                            <AgentsTab
                                data={data}
                                setData={setData}
                                errors={errors}
                                whatsappSources={whatsapp_sources || []}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                            />
                        </TabsContent>
                    )}

                    {/* Multiple Campaign: Options Tab */}
                    {activeTab === "automation" && (
                        <TabsContent value="automation" className="mt-0">
                            <AutomationTab
                                data={data}
                                setData={setData}
                                errors={errors}
                                campaign={defaultCampaign}
                                templates={templates}
                                whatsappSources={whatsapp_sources || []}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                            />
                        </TabsContent>
                    )}

                    {activeTab === "webhooks" && (
                        <TabsContent value="webhooks" className="mt-0">
                            <IntentionWebhookTab
                                data={data}
                                setData={setData}
                                errors={errors}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                            />
                        </TabsContent>
                    )}

                    {activeTab === "templates" && (
                        <TabsContent value="templates" className="mt-0">
                            <TemplatesTab
                                campaign={defaultCampaign}
                                templates={templates}
                                selectedWhatsappSource={selectedWhatsappSource}
                            />
                        </TabsContent>
                    )}
                </Tabs>
            </div>
        </AppLayout>
    );
}
