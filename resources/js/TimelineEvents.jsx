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
                // eslint-disable-next-line no-console
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
            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-500 mx-auto mb-4"></div>
                    <div className="text-xl text-gray-600">Loading timeline events...</div>
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
        <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
            <div className="container mx-auto px-4 py-8">
                {/* Header */}
                <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-4xl font-bold text-gray-800 mb-2">
                                🏁 RaceLab Timeline
                            </h1>
                            <p className="text-gray-600">
                                Track and debug your Laravel application's execution flow
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            {stats && (
                                <div className="text-center bg-blue-50 rounded-lg px-6 py-3">
                                    <div className="text-3xl font-bold text-blue-600">
                                        {stats.total_requests}
                                    </div>
                                    <div className="text-sm text-gray-600">Requests</div>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {/* Controls */}
                    <div className="flex items-center gap-4 mt-6 pt-6 border-t border-gray-200">
                        <button
                            onClick={fetchEvents}
                            className="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center gap-2"
                        >
                            🔄 Refresh
                        </button>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                                className="w-4 h-4"
                            />
                            <span className="text-sm text-gray-700">Auto-refresh (2s)</span>
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