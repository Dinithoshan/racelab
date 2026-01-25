import React from 'react';
import { getEventColor } from '../utils/colors';
import { formatTimestamp } from '../utils/formatting';
import EventDetails from './EventDetails';

/**
 * Component for displaying a single timeline event
 */
export default function EventItem({ 
    event, 
    index,
    isSelected,
    onSelect,
    copiedEventId,
    onCopyQuery,
    showFullTrace,
    onToggleFullTrace
}) {
    return (
        <div 
            key={event.id || index}
            className={`border-l-4 pl-4 py-2 cursor-pointer transition-all ${getEventColor(event.type)} ${
                isSelected ? 'ring-2 ring-[#667eea]' : ''
            }`}
            onClick={() => onSelect(isSelected ? null : event)}
        >
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <div className="flex items-center">
                        <span className="font-semibold text-sm text-[#e2e8f0]">{event.label || event.type}</span>
                        {event.elapsed_time && (
                            <span className="text-xs text-[#a0aec0] ml-2">
                                +{event.elapsed_time?.toFixed(2)}ms
                            </span>
                        )}
                    </div>
                    {isSelected && (
                        <EventDetails
                            event={event}
                            copiedEventId={copiedEventId}
                            onCopyQuery={onCopyQuery}
                            showFullTrace={showFullTrace}
                            onToggleFullTrace={onToggleFullTrace}
                        />
                    )}
                </div>
                <div className="text-xs text-[#a0aec0] mr-2">
                    {formatTimestamp(event.occurred_at)}
                </div>
            </div>
        </div>
    );
}
