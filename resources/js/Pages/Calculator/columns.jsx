import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/react";
import { format } from "date-fns";
import { Calculator, Trash2 } from "lucide-react";

export const getCalculatorColumns = (handleDelete) => [
    {
        accessorKey: "name",
        header: "Nombre",
        cell: ({ row }) => {
            const calculator = row.original;
            return (
                <div className="flex items-center gap-2">
                    <Calculator className="h-4 w-4 text-green-600" />
                    <Link
                        href={route("calculator.show", calculator.id)}
                        className="font-medium hover:underline"
                    >
                        {calculator.name}
                    </Link>
                </div>
            );
        },
    },
    {
        accessorKey: "created_at",
        header: "Fechas",
        cell: ({ row }) => {
            const calculator = row.original;
            return (
                <div className="text-sm">
                    <div className="text-gray-500">
                        <span className="font-medium">Creación:</span>{" "}
                        {format(
                            new Date(calculator.created_at),
                            "dd/MM/yyyy HH:mm"
                        )}
                    </div>
                    <div className="text-gray-500">
                        <span className="font-medium">Modificación:</span>{" "}
                        {format(
                            new Date(calculator.updated_at),
                            "dd/MM/yyyy HH:mm"
                        )}
                    </div>
                </div>
            );
        },
    },
    {
        id: "actions",
        header: "",
        cell: ({ row }) => {
            const calculator = row.original;
            return (
                <div className="flex items-center justify-end">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(calculator)}
                        className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            );
        },
    },
];
