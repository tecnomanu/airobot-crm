import { useState, useEffect } from "react";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import { X } from "lucide-react";

export default function CustomFunctionModal({ open, onOpenChange, function: func, onSave }) {
    const [formData, setFormData] = useState({
        name: "",
        description: "",
        http_method: "POST",
        url: "",
        timeout_ms: 120000,
        headers: [],
        query_params: [],
        parameters_type: "json",
        json_schema: "",
        payload_args_only: false,
        response_variables: [],
        speak_during_execution: false,
        speak_after_execution: true,
    });

    useEffect(() => {
        if (func) {
            setFormData({
                name: func.name || "",
                description: func.description || "",
                http_method: func.http_method || "POST",
                url: func.url || "",
                timeout_ms: func.timeout_ms || 120000,
                headers: func.headers || [],
                query_params: func.query_params || [],
                parameters_type: func.parameters_type || "json",
                json_schema: func.json_schema || "",
                payload_args_only: func.payload_args_only || false,
                response_variables: func.response_variables || [],
                speak_during_execution: func.speak_during_execution || false,
                speak_after_execution: func.speak_after_execution !== undefined ? func.speak_after_execution : true,
            });
        } else {
            setFormData({
                name: "",
                description: "",
                http_method: "POST",
                url: "",
                timeout_ms: 120000,
                headers: [],
                query_params: [],
                parameters_type: "json",
                json_schema: "",
                payload_args_only: false,
                response_variables: [],
                speak_during_execution: false,
                speak_after_execution: true,
            });
        }
    }, [func, open]);

    const handleAddKeyValue = (field) => {
        setFormData(prev => ({
            ...prev,
            [field]: [...prev[field], { key: "", value: "" }]
        }));
    };

    const handleUpdateKeyValue = (field, index, key, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: prev[field].map((item, i) => 
                i === index ? { ...item, [key]: value } : item
            )
        }));
    };

    const handleRemoveKeyValue = (field, index) => {
        setFormData(prev => ({
            ...prev,
            [field]: prev[field].filter((_, i) => i !== index)
        }));
    };

    const handleFormatJSON = () => {
        try {
            const parsed = JSON.parse(formData.json_schema);
            setFormData(prev => ({
                ...prev,
                json_schema: JSON.stringify(parsed, null, 2)
            }));
        } catch (e) {
            // Invalid JSON, do nothing
        }
    };

    const handleSave = () => {
        if (!formData.name || !formData.url) {
            return;
        }
        onSave(formData);
        onOpenChange(false);
    };

    const jsonExamples = [
        {
            name: "example 1",
            schema: JSON.stringify({
                type: "object",
                properties: {
                    name: { type: "string" },
                    age: { type: "number" }
                },
                required: ["name"]
            }, null, 2)
        },
        {
            name: "example 2",
            schema: JSON.stringify({
                type: "object",
                properties: {
                    email: { type: "string", format: "email" },
                    phone: { type: "string" }
                },
                required: ["email"]
            }, null, 2)
        },
        {
            name: "example 3",
            schema: JSON.stringify({
                type: "object",
                properties: {
                    action: { type: "string", enum: ["create", "update", "delete"] },
                    data: { type: "object" }
                },
                required: ["action"]
            }, null, 2)
        }
    ];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Custom Function</DialogTitle>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    {/* Name */}
                    <div className="space-y-1.5">
                        <Label htmlFor="func_name">Name</Label>
                        <Input
                            id="func_name"
                            value={formData.name}
                            onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                            placeholder="Enter the name of the custom function"
                        />
                    </div>

                    {/* Description */}
                    <div className="space-y-1.5">
                        <Label htmlFor="func_description">Description</Label>
                        <Textarea
                            id="func_description"
                            value={formData.description}
                            onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                            placeholder="Enter the description of the custom function"
                            rows={3}
                        />
                    </div>

                    {/* API Endpoint */}
                    <div className="space-y-1.5">
                        <Label>API Endpoint</Label>
                        <p className="text-xs text-muted-foreground">
                            The API Endpoint is the address of the service you are connecting to
                        </p>
                        <div className="flex gap-2">
                            <Select
                                value={formData.http_method}
                                onValueChange={(value) => setFormData(prev => ({ ...prev, http_method: value }))}
                            >
                                <SelectTrigger className="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="GET">GET</SelectItem>
                                    <SelectItem value="POST">POST</SelectItem>
                                    <SelectItem value="PUT">PUT</SelectItem>
                                    <SelectItem value="PATCH">PATCH</SelectItem>
                                    <SelectItem value="DELETE">DELETE</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                value={formData.url}
                                onChange={(e) => setFormData(prev => ({ ...prev, url: e.target.value }))}
                                placeholder="Enter the URL of the custom function"
                                className="flex-1"
                            />
                        </div>
                    </div>

                    {/* Timeout */}
                    <div className="space-y-1.5">
                        <Label htmlFor="func_timeout">Timeout (ms)</Label>
                        <div className="flex items-center gap-2">
                            <Input
                                id="func_timeout"
                                type="number"
                                value={formData.timeout_ms}
                                onChange={(e) => setFormData(prev => ({ ...prev, timeout_ms: parseInt(e.target.value) || 120000 }))}
                                className="w-32"
                            />
                            <span className="text-sm text-muted-foreground">milliseconds</span>
                        </div>
                    </div>

                    {/* Headers */}
                    <div className="space-y-1.5">
                        <Label>Headers</Label>
                        <p className="text-xs text-muted-foreground">
                            Specify the HTTP headers required for your API request.
                        </p>
                        <div className="space-y-2">
                            {formData.headers.map((header, idx) => (
                                <div key={idx} className="flex gap-2">
                                    <Input
                                        placeholder="Key"
                                        value={header.key}
                                        onChange={(e) => handleUpdateKeyValue("headers", idx, "key", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Input
                                        placeholder="Value"
                                        value={header.value}
                                        onChange={(e) => handleUpdateKeyValue("headers", idx, "value", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRemoveKeyValue("headers", idx)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleAddKeyValue("headers")}
                            >
                                + New key value pair
                            </Button>
                        </div>
                    </div>

                    {/* Query Parameters */}
                    <div className="space-y-1.5">
                        <Label>Query Parameters</Label>
                        <p className="text-xs text-muted-foreground">
                            Query string parameters to append to the URL.
                        </p>
                        <div className="space-y-2">
                            {formData.query_params.map((param, idx) => (
                                <div key={idx} className="flex gap-2">
                                    <Input
                                        placeholder="Key"
                                        value={param.key}
                                        onChange={(e) => handleUpdateKeyValue("query_params", idx, "key", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Input
                                        placeholder="Value"
                                        value={param.value}
                                        onChange={(e) => handleUpdateKeyValue("query_params", idx, "value", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRemoveKeyValue("query_params", idx)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleAddKeyValue("query_params")}
                            >
                                + New key value pair
                            </Button>
                        </div>
                    </div>

                    {/* Parameters */}
                    <div className="space-y-1.5">
                        <Label>Parameters (Optional)</Label>
                        <p className="text-xs text-muted-foreground">
                            JSON schema that defines the format in which the LLM will return. Please refer to the{" "}
                            <a href="#" className="text-primary underline">docs</a>.
                        </p>
                        <div className="flex items-center justify-between">
                            <Tabs value={formData.parameters_type} onValueChange={(value) => setFormData(prev => ({ ...prev, parameters_type: value }))}>
                                <TabsList>
                                    <TabsTrigger value="json">JSON</TabsTrigger>
                                    <TabsTrigger value="form">Form</TabsTrigger>
                                </TabsList>
                            </Tabs>
                            <div className="flex items-center gap-2">
                                <Label htmlFor="payload_args" className="text-xs">Payload: args only</Label>
                                <Switch
                                    id="payload_args"
                                    checked={formData.payload_args_only}
                                    onCheckedChange={(checked) => setFormData(prev => ({ ...prev, payload_args_only: checked }))}
                                />
                            </div>
                        </div>
                        <Tabs value={formData.parameters_type}>
                            <TabsContent value="json" className="mt-2">
                                <Textarea
                                    value={formData.json_schema}
                                    onChange={(e) => setFormData(prev => ({ ...prev, json_schema: e.target.value }))}
                                    placeholder="Enter JSON Schema here..."
                                    rows={8}
                                    className="font-mono text-sm bg-muted"
                                />
                                <div className="flex gap-2 mt-2">
                                    {jsonExamples.map((example, idx) => (
                                        <Button
                                            key={idx}
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setFormData(prev => ({ ...prev, json_schema: example.schema }))}
                                        >
                                            {example.name}
                                        </Button>
                                    ))}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleFormatJSON}
                                    >
                                        Format JSON
                                    </Button>
                                </div>
                            </TabsContent>
                            <TabsContent value="form" className="mt-2">
                                <p className="text-sm text-muted-foreground">Form editor coming soon</p>
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Response Variables */}
                    <div className="space-y-1.5">
                        <Label>Response Variables</Label>
                        <p className="text-xs text-muted-foreground">
                            Extracted values from API response saved as dynamic variables.
                        </p>
                        <div className="space-y-2">
                            {formData.response_variables.map((variable, idx) => (
                                <div key={idx} className="flex gap-2">
                                    <Input
                                        placeholder="Key"
                                        value={variable.key}
                                        onChange={(e) => handleUpdateKeyValue("response_variables", idx, "key", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Input
                                        placeholder="Value"
                                        value={variable.value}
                                        onChange={(e) => handleUpdateKeyValue("response_variables", idx, "value", e.target.value)}
                                        className="flex-1"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRemoveKeyValue("response_variables", idx)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleAddKeyValue("response_variables")}
                            >
                                + New key value pair
                            </Button>
                        </div>
                    </div>

                    {/* Execution Settings */}
                    <div className="space-y-3">
                        <Label>Execution Settings</Label>
                        <div className="space-y-3">
                            <div className="flex items-start justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="speak_during" className="text-sm">Speak During Execution</Label>
                                    <p className="text-xs text-muted-foreground">
                                        If the function takes over 2 seconds, the agent can say something like: "Let me check that for you."
                                    </p>
                                </div>
                                <Switch
                                    id="speak_during"
                                    checked={formData.speak_during_execution}
                                    onCheckedChange={(checked) => setFormData(prev => ({ ...prev, speak_during_execution: checked }))}
                                />
                            </div>
                            <div className="flex items-start justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="speak_after" className="text-sm">Speak After Execution</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Unselect if you want to run the function silently, such as uploading the call result to the server silently.
                                    </p>
                                </div>
                                <Switch
                                    id="speak_after"
                                    checked={formData.speak_after_execution}
                                    onCheckedChange={(checked) => setFormData(prev => ({ ...prev, speak_after_execution: checked }))}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button onClick={handleSave} disabled={!formData.name || !formData.url}>
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

