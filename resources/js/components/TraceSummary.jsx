import React from 'react';
import { getSourceColors, getSourceLabels } from '../utils/colors';

/**
 * Component for displaying trace summary information
 */
export default function TraceSummary({ traceSummary }) {
    if (!traceSummary) return null;

    const { file, line, description, short_path, source } = traceSummary;
    const sourceColors = getSourceColors();
    const sourceLabels = getSourceLabels();

    return (
        <div className="mb-3 mt-1 space-y-2">
            <div className={`inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border ${sourceColors[source] || sourceColors.unknown}`}>
                <span className="font-semibold">📍 Query Origin:</span>
                <span className="ml-2 font-mono">
                    {short_path || file?.split('/').pop() || 'Unknown'}:{line}
                </span>
            </div>
            {description && (
                <div className="text-xs text-[#cbd5e0] ml-1 font-mono bg-[#2d3748] p-2 rounded border border-[#4a5568] max-w-[80%] break-all overflow-hidden">
                    {description}
                </div>
            )}
            <div className="text-xs text-[#a0aec0] ml-1">
                Source: {sourceLabels[source] || 'Unknown'}
            </div>
        </div>
    );
}
