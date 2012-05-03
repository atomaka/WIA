class SetDefaultVisits < ActiveRecord::Migration
  def up
    change_column :links, :visits, :integer, :null => false, :default => 0
  end

  def down
    raise ActiveRecord::IrreversibleMigration, "Can't remove the default"
  end
end
