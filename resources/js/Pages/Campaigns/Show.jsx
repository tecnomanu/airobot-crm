import { useState, useEffect } from "react";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { ArrowLeft, Save, AlertTriangle } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Link } from "@inertiajs/react";
import BasicInfoTab from "./Partials/BasicInfoTab";
import AgentsTab from "./Partials/AgentsTab";
import AutomationTab from "./Partials/AutomationTab";
import TemplatesTab from "./Partials/TemplatesTab";

export default function CampaignShow({ campaign, templates, clients, whatsapp_sources, webhook_sources }) {
    const [activeTab, setActiveTab] = useState("basic");

    // Preparar datos con nueva estructura de modelos relacionados
    const callAgent = campaign.call_agent || { name: "", provider: "", config: {}, enabled: true };
    const whatsappAgent = campaign.whatsapp_agent || { name: "", source_id: null, config: {}, enabled: true };
    
    // Convertir opciones de array a objeto indexado por option_key
    const optionsArray = campaign.options || [];
    const optionsMap = {};
    optionsArray.forEach(opt => {
        optionsMap[opt.option_key] = opt;
    });

    const { data, setData, put, processing, errors } = useForm({
        name: campaign.name || "",
        client_id: campaign.client_id || "",
        description: campaign.description || "",
        status: campaign.status || "active",
        auto_process_enabled: campaign.auto_process_enabled !== undefined ? campaign.auto_process_enabled : true,
        
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
            enabled: whatsappAgent.enabled !== undefined ? whatsappAgent.enabled : true,
        },
        
        // Options (asegurar que siempre existan las 4)
        options: [
            optionsMap['1'] || { option_key: '1', action: 'skip', source_id: null, template_id: null, message: '', delay: 5, enabled: true },
            optionsMap['2'] || { option_key: '2', action: 'skip', source_id: null, template_id: null, message: '', delay: 5, enabled: true },
            optionsMap['i'] || { option_key: 'i', action: 'skip', source_id: null, template_id: null, message: '', delay: 5, enabled: true },
            optionsMap['t'] || { option_key: 't', action: 'skip', source_id: null, template_id: null, message: '', delay: 5, enabled: true },
        ],
    });
    
    // Obtener la fuente seleccionada actual
    const selectedWhatsappSource = whatsapp_sources?.find(s => s.id === data.whatsapp_agent.source_id);

    // Determinar qué tab contiene errores
    const getTabsWithErrors = () => {
        const tabs = {
            basic: ['name', 'client_id', 'description', 'status'],
            agents: [
                'call_agent', 'call_agent.name', 'call_agent.provider', 'call_agent.config',
                'whatsapp_agent', 'whatsapp_agent.name', 'whatsapp_agent.source_id', 'whatsapp_agent.config'
            ],
            automation: ['options'],
        };

        const tabsWithErrors = {};
        Object.entries(tabs).forEach(([tabKey, fields]) => {
            const hasError = fields.some(field => errors[field]);
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
                description: `Se encontraron ${Object.keys(errors).length} error(es) de validación`,
                duration: 5000,
            });
        }
    }, [errors]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        put(route("campaigns.update", campaign.id), {
            preserveScroll: true,
            preserveState: true,
            only: ['campaign', 'errors'],
            onSuccess: () => {
                toast.success("Campaña actualizada exitosamente");
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
            },
        });
    };

    return (
        <AppLayout>
            <Head title={`Campaña: ${campaign.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route("campaigns.index")}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold">{campaign.name}</h1>
                            <p className="text-muted-foreground">
                                Configuración de campaña
                            </p>
                        </div>
                    </div>
                    <Button onClick={handleSubmit} disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                </div>

                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="basic" className="relative">
                            Información Básica
                            {tabsWithErrors.basic && (
                                <AlertTriangle className="ml-2 h-4 w-4 text-amber-500" />
                            )}
                        </TabsTrigger>
                        <TabsTrigger value="agents" className="relative">
                            Agentes
                            {tabsWithErrors.agents && (
                                <AlertTriangle className="ml-2 h-4 w-4 text-amber-500" />
                            )}
                        </TabsTrigger>
                        <TabsTrigger value="automation" className="relative">
                            Opciones & Automatización
                            {tabsWithErrors.automation && (
                                <AlertTriangle className="ml-2 h-4 w-4 text-amber-500" />
                            )}
                        </TabsTrigger>
                        <TabsTrigger value="templates">Plantillas</TabsTrigger>
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

                    <TabsContent value="templates">
                        <TemplatesTab
                            campaign={campaign}
                            templates={templates}
                            selectedWhatsappSource={selectedWhatsappSource}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
