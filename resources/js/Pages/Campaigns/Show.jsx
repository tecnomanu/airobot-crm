import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AppLayout from "@/Layouts/AppLayout";
import { Head, useForm } from "@inertiajs/react";
import { AlertTriangle, GitBranch, Save, Zap } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import AgentsTab from "./Partials/AgentsTab";
import AutomationTab from "./Partials/AutomationTab";
import BasicInfoTab from "./Partials/BasicInfoTab";
import DirectActionTab from "./Partials/DirectActionTab";
import IntentionWebhookTab from "./Partials/IntentionWebhookTab";
import TemplatesTab from "./Partials/TemplatesTab";

export default function CampaignShow({
    campaign,
    templates,
    clients,
    whatsapp_sources,
    webhook_sources,
}) {
    const [activeTab, setActiveTab] = useState("basic");

    // Determine campaign type
    const isDirectCampaign = campaign.strategy_type === "direct";

    // Preparar datos con nueva estructura de modelos relacionados
    const callAgent = campaign.call_agent || {
        name: "",
        provider: "",
        config: {},
        enabled: true,
    };
    const whatsappAgent = campaign.whatsapp_agent || {
        name: "",
        source_id: null,
        config: {},
        enabled: true,
    };

    // Direct campaign config from configuration field
    const directConfig = campaign.configuration || {};

    // Convertir opciones de array a objeto indexado por option_key
    const optionsArray = campaign.options || [];
    const optionsMap = {};
    optionsArray.forEach((opt) => {
        optionsMap[opt.option_key] = opt;
    });

    const { data, setData, put, processing, errors } = useForm({
        name: campaign.name || "",
        client_id: campaign.client_id || "",
        description: campaign.description || "",
        status: campaign.status || "active",
        slug: campaign.slug || "",
        strategy_type: campaign.strategy_type || "dynamic",
        auto_process_enabled:
            campaign.auto_process_enabled !== undefined
                ? campaign.auto_process_enabled
                : true,

        // Direct campaign fields
        direct_action: directConfig.trigger_action || "skip",
        direct_source_id: directConfig.source_id || null,
        direct_message: directConfig.message || "",
        direct_template_id: directConfig.template_id || null,

        // Intention Webhooks
        intention_interested_webhook_id:
            campaign.intention_interested_webhook_id || null,
        intention_not_interested_webhook_id:
            campaign.intention_not_interested_webhook_id || null,
        send_intention_interested_webhook:
            campaign.send_intention_interested_webhook || false,
        send_intention_not_interested_webhook:
            campaign.send_intention_not_interested_webhook || false,

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

        // Options (solo para campañas múltiples)
        options: isDirectCampaign ? [] : [
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

        put(route("campaigns.update", campaign.id), {
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
    };

    // Get tabs based on campaign type
    const getTabsConfig = () => {
        if (isDirectCampaign) {
            return [
                { value: "basic", label: "Información Básica", hasError: tabsWithErrors.basic },
                { value: "action", label: "Acción Directa", hasError: false },
                { value: "agents", label: "Agentes", hasError: tabsWithErrors.agents },
                { value: "webhooks", label: "Webhooks de Intención", hasError: false },
            ];
        }
        return [
            { value: "basic", label: "Información Básica", hasError: tabsWithErrors.basic },
            { value: "agents", label: "Agentes", hasError: tabsWithErrors.agents },
            { value: "automation", label: "Opciones & Automatización", hasError: tabsWithErrors.automation },
            { value: "webhooks", label: "Webhooks de Intención", hasError: false },
            { value: "templates", label: "Plantillas", hasError: false },
        ];
    };

    const tabsConfig = getTabsConfig();

    return (
        <AppLayout
            header={{
                title: campaign.name,
                subtitle: (
                    <div className="flex items-center gap-2">
                        <span>Configuración de campaña</span>
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
                        className="h-8 text-xs px-2"
                        disabled={processing}
                    >
                        <Save className="h-3.5 w-3.5 mr-1.5" />
                        Guardar Cambios
                    </Button>
                ),
            }}
        >
            <Head title={`Campaña: ${campaign.name}`} />

            <div className="space-y-6">
                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className={`grid w-full ${isDirectCampaign ? "grid-cols-4" : "grid-cols-5"}`}>
                        {tabsConfig.map((tab) => (
                            <TabsTrigger key={tab.value} value={tab.value} className="relative">
                                {tab.label}
                                {tab.hasError && (
                                    <AlertTriangle className="ml-2 h-4 w-4 text-amber-500" />
                                )}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    <TabsContent value="basic">
                        <BasicInfoTab
                            data={data}
                            setData={setData}
                            errors={errors}
                            clients={clients}
                            campaign={campaign}
                        />
                    </TabsContent>

                    {/* Direct Campaign: Action Tab */}
                    {isDirectCampaign && (
                        <TabsContent value="action">
                            <DirectActionTab
                                data={data}
                                setData={setData}
                                campaign={campaign}
                                templates={templates}
                                whatsappSources={whatsapp_sources || []}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                            />
                        </TabsContent>
                    )}

                    <TabsContent value="agents">
                        <AgentsTab
                            data={data}
                            setData={setData}
                            errors={errors}
                            whatsappSources={whatsapp_sources || []}
                            webhookSources={webhook_sources || []}
                            clients={clients || []}
                        />
                    </TabsContent>

                    {/* Multiple Campaign: Options Tab */}
                    {!isDirectCampaign && (
                        <TabsContent value="automation">
                            <AutomationTab
                                data={data}
                                setData={setData}
                                errors={errors}
                                campaign={campaign}
                                templates={templates}
                                whatsappSources={whatsapp_sources || []}
                                webhookSources={webhook_sources || []}
                                clients={clients || []}
                            />
                        </TabsContent>
                    )}

                    <TabsContent value="webhooks">
                        <IntentionWebhookTab
                            data={data}
                            setData={setData}
                            errors={errors}
                            webhookSources={webhook_sources || []}
                            clients={clients || []}
                        />
                    </TabsContent>

                    {!isDirectCampaign && (
                        <TabsContent value="templates">
                            <TemplatesTab
                                campaign={campaign}
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
