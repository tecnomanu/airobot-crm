import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";

const WELCOME_MESSAGE_OPTIONS = [
    {
        value: "user_speaks_first",
        label: "User speaks first",
        description: "El usuario habla primero, el agente espera",
    },
    {
        value: "ai_speaks_first",
        label: "AI speaks first",
        description: "El agente habla primero con un mensaje de bienvenida",
    },
];

export default function WelcomeMessageSelect({
    value,
    onValueChange,
    onMessageChange,
    className,
}) {
    const selectedOption = WELCOME_MESSAGE_OPTIONS.find(
        (opt) => opt.value === value
    ) || WELCOME_MESSAGE_OPTIONS[0];

    return (
        <div className="space-y-1.5">
            <Label className="text-xs">Welcome Message</Label>
            <Select
                value={value || "user_speaks_first"}
                onValueChange={(selectedValue) => {
                    onValueChange(selectedValue);
                    // Si se selecciona "ai_speaks_first" y no hay mensaje, usar uno por defecto
                    if (selectedValue === "ai_speaks_first" && !onMessageChange) {
                        // El mensaje se manejará en el componente padre
                    }
                }}
            >
                <SelectTrigger className={className}>
                    <SelectValue>
                        {selectedOption.label}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    {WELCOME_MESSAGE_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            <div className="flex flex-col">
                                <span>{option.label}</span>
                                <span className="text-xs text-muted-foreground">
                                    {option.description}
                                </span>
                            </div>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

