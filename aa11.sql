truncate admin_operation_log;
truncate asset_release;
truncate asset_transfer;
truncate authentications;
truncate email_logs;
truncate oauth_access_tokens;
truncate oauth_clients;
truncate qiq_order;
truncate recharges;
truncate user_address;
truncate user_assets;
truncate user_config;
truncate user_entrusts;
truncate user_money_log;
truncate user_position;
truncate user_positions;
truncate user_qianbao_address;
truncate user_trans;
truncate user_withdraw;
delete
from users
where id > 1;
truncate xy_1min_info;
truncate xy_4hour_info;
truncate xy_5min_info;
truncate xy_15min_info;
truncate xy_30min_info;
truncate xy_60min_info;
truncate xy_blocks_msg;
truncate xy_dayk_info;
truncate xy_month_info;
truncate xy_orders;
truncate xy_week_info;
