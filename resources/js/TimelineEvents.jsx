import React, { useState, useEffect } from 'react';
import Clearbutton from './Clearbutton';
import TimelineViewer from './TimelineViewer';

export default function TimelineEvents() {
    const [requests, setRequests] = useState([]);
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState(null);
    const [autoRefresh, setAutoRefresh] = useState(false);

    const fetchEvents = () => {
        fetch('/api/racelabtimelineevents')
            .then((response) => response.json())
            .then((data) => {
                setRequests(data.data || []);
                setStats({
                    total_requests: data.total_requests || 0,
                });
                setLoading(false);
            })
            .catch((error) => {
                console.error('Error fetching events:', error);
                setLoading(false);
            });
    };

    useEffect(() => {
        fetchEvents();
    }, []);

    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(fetchEvents, 2000);
        return () => clearInterval(interval);
    }, [autoRefresh]);
    
    if (loading) {
        return (
            <div className="min-h-screen bg-[#1a202c] flex items-center justify-center">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-[#667eea] mx-auto mb-4"></div>
                    <div className="text-xl text-[#cbd5e0]">Loading timeline events...</div>
                </div>
            </div>
        );
    }

    function clearEvents() {
        fetch('/api/racelabtimelineevents/flush', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        })
            .then((response) => response.json())
            .then((data) => {
                setRequests([]);
                setStats({ total_requests: 0 });
            })
            .catch((error) => {
                console.error('Error clearing events:', error);
            });
    }

    return (
        <div className="min-h-screen bg-[#1a202c]">
            <div className="container mx-auto px-4 py-8">
                {/* Header */}
                <div className="bg-[#2d3748] rounded-lg shadow-xl border border-[#4a5568] p-6 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-5xl font-bold text-[#667eea] mb-2">
                                RaceLab Timeline
                            </h1>
                            <p className="text-[#cbd5e0]">
                                Track and debug your Laravel application's execution flow
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            {stats && (
                                <div className="text-center bg-[#4a5568] rounded-lg px-6 py-3 border border-[#667eea]/30">
                                    <div className="text-3xl font-bold text-[#667eea]">
                                        {stats.total_requests}
                                    </div>
                                    <div className="text-sm text-[#cbd5e0]">Requests</div>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {/* Controls */}
                    <div className="flex items-center gap-4 mt-6 pt-6 border-t border-[#4a5568]">
                        <button
                            onClick={fetchEvents}
                            className="px-4 py-2 bg-[#667eea] hover:bg-[#5568d3] text-white rounded-lg transition-colors flex items-center gap-2 font-medium shadow-lg hover:shadow-xl"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                                className="w-4 h-4 rounded border-[#4a5568] bg-[#2d3748] text-[#667eea] focus:ring-[#667eea] focus:ring-offset-[#1a202c]"
                            />
                            <span className="text-sm text-[#cbd5e0]">Auto-refresh (2s)</span>
                        </label>
                        <div className="flex-1"></div>
                        <Clearbutton clearEvents={clearEvents} />
                    </div>
                </div>

                {/* Timeline */}
                <TimelineViewer requests={requests} />
            </div>
        </div>
    );
}