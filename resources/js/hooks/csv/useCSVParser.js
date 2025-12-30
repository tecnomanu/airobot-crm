
import { useCallback, useState } from 'react';

export function useCSVParser() {
    const [parsing, setParsing] = useState(false);
    const [error, setError] = useState(null);

    const detectDelimiter = (text) => {
        const delimiters = [',', ';', '\t', '|'];
        const firstLine = text.split('\n')[0];

        let bestDelimiter = ',';
        let maxCount = 0;

        delimiters.forEach(delimiter => {
            const count = (firstLine.match(new RegExp(`\\${delimiter}`, 'g')) || []).length;
            if (count > maxCount) {
                maxCount = count;
                bestDelimiter = delimiter;
            }
        });

        return bestDelimiter;
    };

    const parseCSV = useCallback(async (file) => {
        setParsing(true);
        setError(null);

        try {
            const text = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = () => reject(new Error("Error al leer el archivo"));
                reader.readAsText(file);
            });

            if (!text.trim()) {
                throw new Error("El archivo está vacío");
            }

            const delimiter = detectDelimiter(text);
            const lines = text.split(/\r\n|\n/).filter(line => line.trim());

            if (lines.length < 1) {
                throw new Error("El formato del archivo no es válido");
            }

            // Parse headers (assuming first row is header)
            const headers = lines[0].split(delimiter).map(h => h.trim().replace(/^["']|["']$/g, ''));

            // Parse rows
            const rows = lines.slice(1).map((line, index) => {
                // Handle quotes and delimiters properly
                const values = [];
                let current = '';
                let inQuotes = false;

                for (let i = 0; i < line.length; i++) {
                    const char = line[i];
                    if (char === '"') {
                        if (inQuotes && line[i + 1] === '"') {
                            current += '"';
                            i++;
                        } else {
                            inQuotes = !inQuotes;
                        }
                    } else if (char === delimiter && !inQuotes) {
                        values.push(current.trim().replace(/^["']|["']$/g, ''));
                        current = '';
                    } else {
                        current += char;
                    }
                }
                values.push(current.trim().replace(/^["']|["']$/g, ''));

                // Map to object based on headers
                const rowObj = {};
                headers.forEach((header, i) => {
                    rowObj[header] = values[i] || ''; // Handle missing values
                });

                // Add original line index for debugging
                rowObj._lineIndex = index + 2;

                return rowObj;
            });

            return { headers, rows, filename: file.name, totalRows: rows.length };

        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setParsing(false);
        }
    }, []);

    return { parseCSV, parsing, error };
}
