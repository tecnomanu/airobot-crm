import { Switch } from "@/Components/ui/switch";

/**
 * Reusable status toggle switch with label
 *
 * @param {boolean} checked - Current state
 * @param {function} onChange - Callback when toggled
 * @param {string} activeText - Text when active (default: "Activo")
 * @param {string} inactiveText - Text when inactive (default: "Inactivo")
 * @param {string} activeColor - Tailwind color class (default: "green")
 * @param {boolean} showLabel - Show text label (default: true)
 * @param {boolean} disabled - Disable switch
 */
export default function StatusSwitch({
    checked,
    onChange,
    activeText = "Activo",
    inactiveText = "Inactivo",
    activeColor = "green",
    showLabel = true,
    disabled = false,
}) {
    const colorClasses = {
        green: "data-[state=checked]:bg-green-600",
        blue: "data-[state=checked]:bg-blue-600",
        purple: "data-[state=checked]:bg-purple-600",
        orange: "data-[state=checked]:bg-orange-600",
    };

    const textColorClasses = {
        green: "text-green-600",
        blue: "text-blue-600",
        purple: "text-purple-600",
        orange: "text-orange-600",
    };

    return (
        <div className="flex items-center gap-2">
            <Switch
                checked={checked}
                onCheckedChange={onChange}
                disabled={disabled}
                className={colorClasses[activeColor] || colorClasses.green}
            />
            {showLabel && (
                <span
                    className={`text-xs font-medium ${
                        checked
                            ? textColorClasses[activeColor] || textColorClasses.green
                            : "text-gray-500"
                    }`}
                >
                    {checked ? activeText : inactiveText}
                </span>
            )}
        </div>
    );
}
