INSERT INTO operators (prefix, is_active, sort) VALUES
  ('010', 1, 1), ('050', 1, 2), ('051', 1, 3), ('055', 1, 4),
  ('060', 1, 5), ('070', 1, 6), ('077', 1, 7), ('099', 1, 8);

INSERT INTO vehicles (name, is_active, sort) VALUES
  ('Ford Transit', 1, 1),
  ('Mercedes Sprinter', 1, 2),
  ('Mercedes Vito', 1, 3),
  ('Volkswagen Crafter', 1, 4),
  ('Renault Master', 1, 5),
  ('Fiat Ducato', 1, 6),
  ('Iveco Daily', 1, 7),
  ('QAZel', 1, 8),
  ('Isuzu', 1, 9),
  ('Hyundai HD', 1, 10),
  ('Digər', 1, 11);

INSERT INTO vehicle_sizes (code, dimensions, is_active, sort) VALUES
  ('S', '2.2 × 1.6 × 1.5 m', 1, 1),
  ('M', '3.0 × 1.7 × 1.8 m', 1, 2),
  ('L', '4.2 × 1.8 × 2.0 m', 1, 3),
  ('XL', '5.0 × 2.0 × 2.2 m', 1, 4);

INSERT INTO cargo_types (name, is_active, sort) VALUES
  ('Ev köçürülməsi', 1, 1),
  ('Ofis köçürülməsi', 1, 2),
  ('Mebel', 1, 3),
  ('Məişət texnikası', 1, 4),
  ('Tikinti materialı', 1, 5),
  ('Mağaza məhsulları', 1, 6),
  ('Digər', 1, 7);
