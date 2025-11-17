import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router } from "@inertiajs/react";
import { ArrowLeft, ExternalLink, MessageSquare, Phone, User } from "lucide-react";

export default function LeadIntencionShow({ lead }) {
    const getIntentionBadge = () => {
        const intention = lead.intention;
        
        if (intention === "interested") {
            return (
                <Badge className="bg-green-100 text-green-800 hover:bg-green-100 text-lg px-4 py-2">
                    ✅ Interested
                </Badge>
            );
        }
        
        if (intention === "not_interested") {
            return (
                <Badge className="bg-red-100 text-red-800 hover:bg-red-100 text-lg px-4 py-2">
                    ❌ Not Interested
                </Badge>
            );
        }
        
        return (
            <Badge variant="outline" className="text-lg px-4 py-2">
                ⏳ Pending Analysis
            </Badge>
        );
    };

    const getStatusBadge = (status) => {
        const colors = {
            pending: "bg-yellow-100 text-yellow-800 hover:bg-yellow-100",
            in_progress: "bg-blue-100 text-blue-800 hover:bg-blue-100",
            contacted: "bg-purple-100 text-purple-800 hover:bg-purple-100",
            closed: "bg-green-100 text-green-800 hover:bg-green-100",
            invalid: "bg-red-100 text-red-800 hover:bg-red-100",
        };
        return (
            <Badge className={colors[status] || "bg-gray-100 text-gray-800"}>
                {lead.status_label}
            </Badge>
        );
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return "-";
        const date = new Date(dateString);
        return date.toLocaleString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <AppLayout>
            <Head title={`Lead Intention - ${lead.phone}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Header con botón de regreso */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => router.visit(route("leads-intencion.index"))}
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Lead Intention Details
                            </h1>
                            <p className="text-muted-foreground">
                                WhatsApp conversation and intention analysis
                            </p>
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route("leads.show", lead.id))}
                    >
                        <ExternalLink className="mr-2 h-4 w-4" />
                        View Full Lead Profile
                    </Button>
                </div>

                {/* Intention Status - Destacado */}
                <Card className="border-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-3">
                            <MessageSquare className="h-6 w-6" />
                            Detected Intention
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            {getIntentionBadge()}
                            <div className="text-right text-sm text-muted-foreground">
                                <div>
                                    Origin: <span className="font-medium">{lead.intention_origin || "N/A"}</span>
                                </div>
                                <div>
                                    Status: <span className="font-medium">{lead.intention_status || "N/A"}</span>
                                </div>
                            </div>
                        </div>
                        {lead.intention && 
                         lead.intention !== "interested" && 
                         lead.intention !== "not_interested" && (
                            <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                <p className="text-sm text-gray-700">
                                    <strong>Content:</strong> {lead.intention}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Contact Info Summary */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-3">
                            <User className="h-5 w-5" />
                            Contact Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p className="text-sm text-muted-foreground">Name</p>
                                <p className="font-medium">{lead.name || "Unknown"}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Phone</p>
                                <p className="font-medium flex items-center gap-2">
                                    <Phone className="h-4 w-4" />
                                    {lead.phone}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Campaign</p>
                                <p className="font-medium">{lead.campaign?.name || "N/A"}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Status</p>
                                {getStatusBadge(lead.status)}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Chat History - Principal */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <MessageSquare className="h-5 w-5" />
                                WhatsApp Conversation
                            </div>
                            <Badge variant="outline">
                                {lead.interactions_count || lead.interactions?.length || 0} messages
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {lead.interactions && lead.interactions.length > 0 ? (
                            <div className="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                                {lead.interactions.map((interaction, index) => {
                                    const isInbound = interaction.direction === "inbound";
                                    return (
                                        <div
                                            key={interaction.id || index}
                                            className={`flex ${isInbound ? "justify-start" : "justify-end"}`}
                                        >
                                            <div
                                                className={`max-w-[70%] rounded-lg p-4 ${
                                                    isInbound
                                                        ? "bg-gray-100 text-gray-900"
                                                        : "bg-blue-500 text-white"
                                                }`}
                                            >
                                                <div className="flex items-center gap-2 mb-2">
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            isInbound
                                                                ? "bg-white text-gray-700 border-gray-300"
                                                                : "bg-blue-600 text-white border-blue-400"
                                                        }
                                                    >
                                                        {isInbound ? "👤 Lead" : "🤖 AIRobot"}
                                                    </Badge>
                                                    <span
                                                        className={`text-xs ${
                                                            isInbound ? "text-gray-500" : "text-blue-100"
                                                        }`}
                                                    >
                                                        {formatDateTime(interaction.created_at)}
                                                    </span>
                                                </div>
                                                <p className="text-sm whitespace-pre-wrap break-words">
                                                    {interaction.content}
                                                </p>
                                                <div
                                                    className={`text-xs mt-2 ${
                                                        isInbound ? "text-gray-400" : "text-blue-100"
                                                    }`}
                                                >
                                                    via {interaction.channel}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-8 text-muted-foreground">
                                <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No interactions recorded yet</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Metadata - Dates */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-muted-foreground">First Contact</p>
                                <p className="font-medium">{formatDateTime(lead.created_at)}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Last Update</p>
                                <p className="font-medium">{formatDateTime(lead.updated_at)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

