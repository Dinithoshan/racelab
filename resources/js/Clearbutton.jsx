import React from 'react';

export default function Clearbutton({clearEvents}) {

    return (
        <div className="container mx-auto p-8 pb-4">
            <button 
                onClick={() => clearEvents()}
                className="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow-md transition-colors duration-200"
            >
                CLEAR
            </button>
        </div>
    )
}