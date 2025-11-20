import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { router } from "@inertiajs/react";
import { ArrowUpDown, Edit, Trash2 } from "lucide-react";

export const getCallAgentColumns = (handleDelete) => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Seleccionar todo"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Seleccionar fila"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: "agent_name",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => (
            <div className="font-medium">
                {row.getValue("agent_name") || "-"}
            </div>
        ),
    },
    {
        accessorKey: "voice_id",
        header: "Voice ID",
        cell: ({ row }) => (
            <div className="text-sm text-muted-foreground">
                {row.getValue("voice_id") || "-"}
            </div>
        ),
    },
    {
        accessorKey: "language",
        header: "Idioma",
        cell: ({ row }) => {
            const language = row.getValue("language");
            return (
                <Badge variant="outline">
                    {language || "N/A"}
                </Badge>
            );
        },
    },
    {
        accessorKey: "version",
        header: "Versión",
        cell: ({ row }) => {
            const version = row.original.version ?? row.original.agent_version ?? 0;
            const isPublished = row.original.is_published ?? false;
            return (
                <div className="flex items-center gap-2">
                    <Badge variant={isPublished ? "default" : "secondary"}>
                        v{version}
                    </Badge>
                    {isPublished && (
                        <Badge className="bg-green-100 text-green-800 hover:bg-green-100 text-xs">
                            Publicado
                        </Badge>
                    )}
                </div>
            );
        },
    },
    {
        accessorKey: "webhook_url",
        header: "Webhook",
        cell: ({ row }) => {
            const webhook = row.getValue("webhook_url");
            return webhook ? (
                <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                    Configurado
                </Badge>
            ) : (
                <Badge variant="outline">Sin webhook</Badge>
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const agent = row.original;
            const agentId = agent.agent_id || agent.id;

            return (
                <div className="flex justify-end items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            router.visit(route("call-agents.show", agentId))
                        }
                    >
                        <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(agent)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            );
        },
    },
];

