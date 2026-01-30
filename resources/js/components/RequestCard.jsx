import React from 'react';
import { formatTimestamp } from '../utils/formatting';
import EventItem from './EventItem';

/**
 * Component for displaying a single request with its events
 */
export default function RequestCard({ 
    request, 
    index,
    isExpanded,
    onToggle,
    selectedEvent,
    onSelectEvent,
    copiedEventId,
    onCopyQuery,
    showFullTrace,
    onToggleFullTrace,
    dbDialect
}) {
    return (
        <div 
            key={request.request_id || index}
            className="bg-[#2d3748] rounded-lg shadow-xl border border-[#4a5568] overflow-hidden"
        >
            {/* Request Summary Header */}
            <div 
                className="bg-linear-to-r from-[#667eea]/20 to-[#9f7aea]/20 p-4 cursor-pointer hover:from-[#667eea]/30 hover:to-[#9f7aea]/30 transition-all border-b border-[#4a5568]"
                onClick={() => onToggle(request.request_id)}
            >
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <span className="text-2xl text-[#e2e8f0]">
                            {isExpanded ? '▼' : '▶'}
                        </span>
                        <div>
                            <div className="font-mono text-sm text-[#cbd5e0]">
                                {(() => {
                                    // Extract path from http_request event if available
                                    const httpRequestEvent = request.events?.find(e => e.type === 'http_request');
                                    if (httpRequestEvent?.decoded_payload?.uri) {
                                        const uri = httpRequestEvent.decoded_payload.uri;
                                        // If it's already a path (starts with /), use it directly
                                        if (uri.startsWith('/')) {
                                            return uri;
                                        }
                                        // Otherwise, try to extract pathname from full URL
                                        try {
                                            return new URL(uri).pathname;
                                        } catch {
                                            return uri;
                                        }
                                    }
                                    return request.request_id;
                                })()}
                            </div>
                            <div className="text-xs text-[#a0aec0] mt-1">
                                Started: {formatTimestamp(request.started_at)}
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-4 text-sm">
                        <div className="text-center">
                            <div className="font-semibold text-[#667eea]">{request.event_count}</div>
                            <div className="text-xs text-[#a0aec0]">Events</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#9f7aea]">{request.query_count}</div>
                            <div className="text-xs text-[#a0aec0]">Queries</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#ed8936]">
                                {request.total_query_time?.toFixed(2)}ms
                            </div>
                            <div className="text-xs text-[#a0aec0]">Query Time</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#48bb78]">
                                {request.duration?.toFixed(2)}ms
                            </div>
                            <div className="text-xs text-[#a0aec0]">Duration</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Event Timeline */}
            {isExpanded && (
                <div className="p-4 space-y-1 bg-[#1a202c]">
                    {request.events.map((event, eventIndex) => (
                        <EventItem
                            key={event.id || eventIndex}
                            event={event}
                            index={eventIndex}
                            isSelected={selectedEvent?.id === event.id}
                            onSelect={onSelectEvent}
                            copiedEventId={copiedEventId}
                            onCopyQuery={onCopyQuery}
                            showFullTrace={showFullTrace}
                            onToggleFullTrace={onToggleFullTrace}
                            dbDialect={dbDialect}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
