import { File } from "lucide-react";
import { useState, useEffect, useRef } from "react";
import { cn } from "@/lib/utils";

export default function CalculatorTitle({ title, onTitleChange, readOnly = false }) {
    const [isEditing, setIsEditing] = useState(false);
    const [editValue, setEditValue] = useState(title);
    const inputRef = useRef(null);

    useEffect(() => {
        setEditValue(title);
    }, [title]);

    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            inputRef.current.select();
        }
    }, [isEditing]);

    const handleBlur = () => {
        setIsEditing(false);
        const trimmedValue = editValue.trim();
        if (trimmedValue && trimmedValue !== title) {
            onTitleChange(trimmedValue);
        } else {
            setEditValue(title);
        }
    };

    const handleKeyDown = (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            handleBlur();
        } else if (e.key === "Escape") {
            setEditValue(title);
            setIsEditing(false);
        }
    };

    if (readOnly) {
        return (
            <div className="flex items-center gap-2 px-2">
                <File className="h-4 w-4 text-green-600" />
                <span className="text-sm font-medium text-gray-900">{title}</span>
            </div>
        );
    }

    return (
        <div className="flex items-center gap-2 px-2">
            <File className="h-4 w-4 text-green-600" />
            {isEditing ? (
                <input
                    ref={inputRef}
                    type="text"
                    value={editValue}
                    onChange={(e) => setEditValue(e.target.value)}
                    onBlur={handleBlur}
                    onKeyDown={handleKeyDown}
                    className={cn(
                        "text-sm font-medium bg-transparent border-b border-blue-500",
                        "outline-none focus:border-blue-600 px-1 py-0.5 min-w-[120px] max-w-[300px]"
                    )}
                    maxLength={255}
                />
            ) : (
                <button
                    onClick={() => !readOnly && setIsEditing(true)}
                    className={cn(
                        "text-sm font-medium text-gray-900 hover:text-gray-700",
                        "px-1 py-0.5 rounded hover:bg-gray-100 transition-colors",
                        "text-left"
                    )}
                    disabled={readOnly}
                >
                    {title || "Hoja sin título"}
                </button>
            )}
        </div>
    );
}

