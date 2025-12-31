import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import AppLayout from "@/Layouts/AppLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { SiGoogle, SiGooglesheets } from "react-icons/si";
import { Unlink } from "lucide-react";

export default function Integrations() {
    const { auth, flash } = usePage().props;
    const googleIntegration = auth.user.google_integration; // Assuming we pass this from HandleInertiaRequests or Controller

    return (
        <AppLayout
            header={{
                title: "Integraciones",
                subtitle: "Gestiona las integraciones de terceros",
            }}
        >
            <Head title="Integraciones" />

            <div className="grid gap-6">
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-3">
                            <SiGooglesheets className="w-8 h-8 text-green-600" />
                            <div>
                                <CardTitle>Google Sheets</CardTitle>
                                <CardDescription>
                                    Conecta tu cuenta de Google para exportar
                                    leads automáticamente.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="text-sm font-medium">
                                    Estado:
                                </span>
                                {googleIntegration ? (
                                    <Badge
                                        variant="success"
                                        className="bg-green-100 text-green-800 hover:bg-green-200"
                                    >
                                        Conectado
                                    </Badge>
                                ) : (
                                    <Badge
                                        variant="outline"
                                        className="text-gray-500"
                                    >
                                        No conectado
                                    </Badge>
                                )}
                            </div>
                            {googleIntegration && (
                                <p className="text-sm text-gray-500">
                                    Conectado como:{" "}
                                    <span className="font-medium text-gray-700 dark:text-gray-300">
                                        {googleIntegration.email}
                                    </span>
                                </p>
                            )}
                        </div>

                        {googleIntegration ? (
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (confirm("¿Estás seguro de desconectar tu cuenta de Google?")) {
                                        router.delete(route("auth.google.disconnect"));
                                    }
                                }}
                            >
                                <Unlink className="mr-2 h-4 w-4" />
                                Desconectar
                            </Button>
                        ) : (
                            <Button asChild>
                                <a href={route("auth.google")}>
                                    <SiGoogle className="mr-2 h-4 w-4" />
                                    Conectar Google
                                </a>
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
