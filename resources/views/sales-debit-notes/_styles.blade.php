@once
<style>
.wazin-note{color:#071633;line-height:1.8}
.wazin-note .wn-hero{background:linear-gradient(135deg,#071633,#0A1730);color:#fff;border-radius:22px;padding:24px;display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;box-shadow:0 16px 40px #07163329}
.wazin-note .wn-hero h3{color:#fff;font-weight:900;margin:0 0 6px}
.wazin-note .wn-hero p{color:#CFEFF3;margin:0;font-weight:600}
.wazin-note .wn-card{background:#fff;border:1px solid #E5E7EB;border-radius:20px;overflow:hidden;margin-bottom:22px;box-shadow:0 4px 16px #07163306}
.wazin-note .wn-heading{padding:18px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.wazin-note .wn-heading h5{margin:0;color:#071633;font-weight:900;font-size:17px}
.wazin-note .wn-heading small,.wazin-note .wn-muted{color:#64748B;font-size:13px;font-weight:600}
.wazin-note .wn-body{padding:20px}
.wazin-note .wn-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.wazin-note .wn-field label,.wazin-note .wn-field>span{display:block;color:#64748B;font-size:13px;margin-bottom:6px}
.wazin-note .wn-field strong{font-weight:800;overflow-wrap:anywhere}
.wazin-note .wn-form-grid{display:grid;grid-template-columns:1fr 2fr;gap:20px}
.wazin-note .form-label{font-weight:800;color:#071633;margin-bottom:8px}
.wazin-note .form-control,.wazin-note .form-select{border:1px solid #E5E7EB;border-radius:14px;padding:10px 14px;min-height:46px;color:#111827;font-size:14px;background-color:#fff}
.wazin-note .form-control:focus,.wazin-note .form-select:focus{border-color:#2F6BFF;box-shadow:0 0 0 3px #2f6bff1f}
.wazin-note .btn{border-radius:14px;font-weight:800;padding:10px 18px;white-space:nowrap}
.wazin-note .btn-primary{background:#2F6BFF;border-color:#2F6BFF;color:white}
.wazin-note .btn-primary:hover{background:#2559D9;border-color:#2559D9}
.wazin-note .wn-back{color:#fff;border:1px solid #ffffff55;background:#ffffff0d}
.wazin-note .wn-back:hover{background:#ffffff22;color:#fff}
.wazin-note .btn-dark{background:#071633;border-color:#071633}
.wazin-note .btn-danger{background:#E63B4A;border-color:#E63B4A}
.wazin-note .btn-sm{padding:7px 14px;font-size:13px;border-radius:12px}
.wazin-note .wn-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.wazin-note .wn-actions form{margin:0}
.wazin-note .wn-search{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.wazin-note .wn-search .form-control{flex:1;min-width:180px}
.wazin-note .wn-table-wrap{overflow-x:auto}
.wazin-note .wn-table{width:100%;border-collapse:collapse;margin:0;font-size:14px}
.wazin-note .wn-table th{background:#071633!important;color:#fff!important;border:1px solid #101F3C;padding:14px 12px;font-weight:900;white-space:nowrap;text-align:center}
.wazin-note .wn-table td{border:1px solid #E5E7EB;padding:14px 12px;text-align:center;vertical-align:middle;font-weight:600}
.wazin-note .wn-table tbody tr:nth-child(even){background:#F8FAFC}
.wazin-note .wn-table tbody tr:hover{background:#F1F5FF}
.wazin-note .wn-product{text-align:right!important;min-width:150px}
.wazin-note .wn-product small{display:block;color:#64748B;margin-top:4px}
.wazin-note .wn-number{font-variant-numeric:tabular-nums;white-space:nowrap}
.wazin-note .wn-entry-table{min-width:1000px}
.wazin-note .wn-entry-table input{min-width:120px;direction:ltr;text-align:center}
.wazin-note .wn-entry-table select{min-width:155px}
.wazin-note .wn-badge{display:inline-block;border-radius:999px;padding:6px 13px;font-size:12px;font-weight:900;white-space:nowrap}
.wazin-note .wn-draft{background:#FFF4D6;color:#805400}
.wazin-note .wn-posted{background:#DCFCE7;color:#166534}
.wazin-note .wn-cancelled{background:#FEE2E2;color:#991B1B}
.wazin-note .wn-help{padding:14px 16px;border:1px solid #CFEFF3;border-radius:14px;background:#EEF9FB;color:#244C60;font-size:13px}
.wazin-note .wn-help p{margin:0 0 4px}
.wazin-note .wn-help p:last-child{margin:0}
.wazin-note .wn-text{white-space:pre-line;overflow-wrap:anywhere;margin:0}
.wazin-note .wn-total{margin-inline-start:auto;width:100%;max-width:400px;break-inside:avoid}
.wazin-note .wn-total-row{display:flex;justify-content:space-between;gap:16px;padding:14px 20px;border-bottom:1px solid #E5E7EB}
.wazin-note .wn-total-row:last-child{background:#071633;color:#fff;font-size:18px;font-weight:900;border:0}
.wazin-note .wn-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}
.wazin-note .wn-empty{padding:35px 15px;color:#64748B}
.wazin-note .alert{border-radius:16px;line-height:1.8;font-weight:600}
.wazin-note .wn-cancel-box{border-color:#FECACA}
.wazin-note .wn-cancel-box .wn-heading{background:#FFF5F5}
.wazin-note .wn-pager{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px}
.wazin-note .wn-pager a,.wazin-note .wn-page{padding:7px 14px;border:1px solid #E5E7EB;border-radius:12px;text-decoration:none;font-weight:700}
.wazin-note .wn-page{background:#2F6BFF;color:#fff}
@media screen and (max-width:767px){.wazin-note .wn-hero{flex-direction:column;align-items:stretch;padding:20px}.wazin-note .wn-grid,.wazin-note .wn-form-grid{grid-template-columns:1fr}.wazin-note .wn-body{padding:16px}.wazin-note .wn-total{max-width:none}.wazin-note .wn-footer .wn-actions{width:100%}.wazin-note .wn-footer .btn{flex:1}}
@media print{.wazin-note{font-size:11px;color:#000}.wazin-note .wn-hero{background:#fff!important;color:#071633;box-shadow:none;border-radius:0;border-bottom:2px solid #071633;padding:0 0 14px}.wazin-note .wn-hero h3,.wazin-note .wn-hero p{color:#071633}.wazin-note .wn-card{box-shadow:none;border-radius:0;margin-bottom:14px}.wazin-note .wn-body,.wazin-note .wn-heading{padding:12px}.wazin-note .wn-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.wazin-note .wn-table-wrap{overflow:visible}.wazin-note .wn-table{font-size:10px;table-layout:fixed}.wazin-note .wn-table th,.wazin-note .wn-table td{padding:8px 5px;white-space:normal;overflow-wrap:anywhere}.wazin-note .wn-table th{background:#EEF2F6!important;color:#071633!important;border-color:#ccc}.wazin-note .wn-product{min-width:0}.wazin-note .wn-table thead{display:table-header-group}.wazin-note .wn-table tr{break-inside:avoid}.wazin-note .wn-total{max-width:320px}.wazin-note .wn-total-row:last-child{background:#F1F5F9;color:#071633;font-size:14px}.wazin-note .wn-no-print{display:none!important}.wazin-note .wn-keep{break-inside:avoid}}
</style>
@endonce
