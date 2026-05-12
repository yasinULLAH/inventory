UPDATE models SET image='uploads/models/model_2_t9_sports_electric_bike.jpg' WHERE id=2;
UPDATE models SET image='uploads/models/model_3_t9_sports_lfp_electric_bike.jpg' WHERE id=3;
UPDATE models SET image='uploads/models/model_5_thrill_pro_electric_bike.jpg' WHERE id=5;
UPDATE models SET image='uploads/models/model_6_thrill_pro_lfp_electric_bike.jpg' WHERE id=6;
UPDATE models SET image='uploads/models/model_9_m6_k6_electric_bike.jpg' WHERE id=9;
UPDATE models SET image='uploads/models/model_10_m6_np_electric_bike.jpg' WHERE id=10;
UPDATE models SET image='uploads/models/model_12_premium_electric_bike.jpg' WHERE id=12;
UPDATE models SET image='uploads/models/model_13_w_bike_h2_electric_bike.jpg' WHERE id=13;
UPDATE bikes b JOIN models m ON m.id=b.model_id SET b.image=m.image WHERE (b.image IS NULL OR b.image='') AND m.image IS NOT NULL AND m.image<>'';
