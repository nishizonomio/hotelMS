<?php
// ...existing code...
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 32px;
      margin-bottom: 32px;
      width: 100%;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
      align-items: stretch;
    }
    .card {
      background: rgba(255,255,255,0.97);
      border-radius: 14px;
      padding: 32px 24px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.10);
      border: 1px solid #e2e8f0;
      min-height: 320px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      width: 100%;
      box-sizing: border-box;
    }
    .table {
      width: 100%;
      table-layout: fixed;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      background: #fff;
      margin-bottom: 0;
      box-sizing: border-box;
    }
    .table th, .table td {
      padding: 12px 10px;
      text-align: center;
      border-bottom: 1px solid #e5e7eb;
      word-break: break-word;
      vertical-align: middle;
    }
    .table th {
      background: #f3f4f6;
      font-weight: 600;
      color: #374151;
      border-bottom: 2px solid #e2e8f0;
    }
    .table tr:last-child td {
      border-bottom: none;
    }
    .table td button {
      min-width: 70px;
    }
    @media (max-width: 900px) {
      .grid {
        grid-template-columns: 1fr;
        gap: 16px;
        max-width: 100%;
      }
      .card {
        min-width: 0;
        width: 100%;
      }
    }
    @media (max-width: 700px) {
      .container {
        padding: 12px 4px;
      }
      .header {
        padding: 18px 8px;
      }
      .card {
        padding: 18px 8px;
      }
      .grid {
        gap: 8px;
      }
      .table th, .table td {
        padding: 8px 4px;
        font-size: 0.95em;
      }
    }
// ...existing code...