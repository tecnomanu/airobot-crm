import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import { Head } from "@inertiajs/react";
import { Copy, CheckCircle, Code, Webhook as WebhookIcon } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";

export default function WebhookIndex({
    webhookUrl,
    examplePayload,
    requiredFields,
}) {
    const [copied, setCopied] = useState(false);
    const [copiedExample, setCopiedExample] = useState(false);

    const copyToClipboard = (text, isCurl = false) => {
        navigator.clipboard.writeText(text);
        if (isCurl) {
            setCopiedExample(true);
            setTimeout(() => setCopiedExample(false), 2000);
        } else {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const curlExample = `curl -X POST ${webhookUrl} \\
  -H "Content-Type: application/json" \\
  -d '${JSON.stringify(examplePayload, null, 2)}'`;

    const phpExample = `<?php
$url = '${webhookUrl}';
$data = ${JSON.stringify(examplePayload, null, 4)};

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;`;

    const jsExample = `fetch('${webhookUrl}', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(${JSON.stringify(examplePayload, null, 4)})
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));`;

    return (
        <AppLayout>
            <Head title="Configuración Webhook" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Configuración de Webhook
                    </h1>
                    <p className="text-muted-foreground">
                        URL y documentación para integrar el registro de leads
                    </p>
                </div>

                {/* Webhook URL */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <WebhookIcon className="h-5 w-5" />
                            URL del Webhook
                        </CardTitle>
                        <CardDescription>
                            Endpoint público para recibir leads
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-2">
                            <code className="flex-1 rounded-md bg-muted px-4 py-3 text-sm font-mono">
                                {webhookUrl}
                            </code>
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() => copyToClipboard(webhookUrl)}
                            >
                                {copied ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <Copy className="h-4 w-4" />
                                )}
                            </Button>
                        </div>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Método: <Badge variant="outline">POST</Badge>
                        </p>
                    </CardContent>
                </Card>

                {/* Required Fields */}
                <Card>
                    <CardHeader>
                        <CardTitle>Campos del Payload</CardTitle>
                        <CardDescription>
                            Estructura del JSON esperado en el body de la petición
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Campo</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Requerido</TableHead>
                                    <TableHead>Descripción</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requiredFields.map((field) => (
                                    <TableRow key={field.field}>
                                        <TableCell className="font-mono text-sm">
                                            {field.field}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{field.type}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            {field.required ? (
                                                <Badge className="bg-red-100 text-red-800 hover:bg-red-100">
                                                    Sí
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">No</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {field.description}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Example Payload */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Code className="h-5 w-5" />
                            Ejemplo de Payload
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="relative">
                            <pre className="rounded-md bg-muted p-4 text-sm overflow-x-auto">
                                <code>{JSON.stringify(examplePayload, null, 2)}</code>
                            </pre>
                            <Button
                                variant="outline"
                                size="sm"
                                className="absolute top-2 right-2"
                                onClick={() =>
                                    copyToClipboard(
                                        JSON.stringify(examplePayload, null, 2)
                                    )
                                }
                            >
                                {copied ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <Copy className="h-4 w-4" />
                                )}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Integration Examples */}
                <Card>
                    <CardHeader>
                        <CardTitle>Ejemplos de Integración</CardTitle>
                        <CardDescription>
                            Código de ejemplo en diferentes lenguajes
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* cURL */}
                        <div>
                            <h3 className="mb-2 font-semibold">cURL</h3>
                            <div className="relative">
                                <pre className="rounded-md bg-muted p-4 text-sm overflow-x-auto">
                                    <code>{curlExample}</code>
                                </pre>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="absolute top-2 right-2"
                                    onClick={() => copyToClipboard(curlExample, true)}
                                >
                                    {copiedExample ? (
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                    ) : (
                                        <Copy className="h-4 w-4" />
                                    )}
                                </Button>
                            </div>
                        </div>

                        {/* PHP */}
                        <div>
                            <h3 className="mb-2 font-semibold">PHP</h3>
                            <div className="relative">
                                <pre className="rounded-md bg-muted p-4 text-sm overflow-x-auto">
                                    <code>{phpExample}</code>
                                </pre>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="absolute top-2 right-2"
                                    onClick={() => copyToClipboard(phpExample, true)}
                                >
                                    {copiedExample ? (
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                    ) : (
                                        <Copy className="h-4 w-4" />
                                    )}
                                </Button>
                            </div>
                        </div>

                        {/* JavaScript */}
                        <div>
                            <h3 className="mb-2 font-semibold">JavaScript (Fetch API)</h3>
                            <div className="relative">
                                <pre className="rounded-md bg-muted p-4 text-sm overflow-x-auto">
                                    <code>{jsExample}</code>
                                </pre>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="absolute top-2 right-2"
                                    onClick={() => copyToClipboard(jsExample, true)}
                                >
                                    {copiedExample ? (
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                    ) : (
                                        <Copy className="h-4 w-4" />
                                    )}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Response Format */}
                <Card>
                    <CardHeader>
                        <CardTitle>Formato de Respuesta</CardTitle>
                        <CardDescription>
                            El webhook responderá con el lead creado/actualizado
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre className="rounded-md bg-muted p-4 text-sm overflow-x-auto">
                            <code>{`{
  "data": {
    "id": 1,
    "phone": "2215648523",
    "name": "Juan",
    "city": "La Plata",
    "option_selected": "Plan A",
    "campaign_id": 1,
    "status": "pending",
    "source": "webhook_inicial",
    "tags": ["tag1", "tag2"],
    "created_at": "2025-11-13T02:48:47.000000Z",
    "updated_at": "2025-11-13T02:48:47.000000Z"
  }
}`}</code>
                        </pre>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

